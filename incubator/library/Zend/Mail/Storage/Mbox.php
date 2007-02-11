<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to version 1.0 of the Zend Framework
 * license, that is bundled with this package in the file LICENSE, and
 * is available through the world-wide-web at the following URL:
 * http://www.zend.com/license/framework/1_0.txt. If you did not receive
 * a copy of the Zend Framework license and are unable to obtain it
 * through the world-wide-web, please send a note to license@zend.com
 * so we can mail you a copy immediately.
 *
 * @package    Zend_Mail
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://www.zend.com/license/framework/1_0.txt Zend Framework License version 1.0
 */


/**
 * Zend_Mail_Storage_Abstract
 */
require_once 'Zend/Mail/Storage/Abstract.php';

/**
 * Zend_Mail_Message
 */
require_once 'Zend/Mail/Message.php';

/**
 * Zend_Mail_Storage_Exception
 */
require_once 'Zend/Mail/Storage/Exception.php';


/**
 * @package    Zend_Mail
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://www.zend.com/license/framework/1_0.txt Zend Framework License version 1.0
 */
class Zend_Mail_Storage_Mbox extends Zend_Mail_Storage_Abstract
{
    /**
     * file handle to mbox file
     */
    private $_fh;

    /**
     * filename of mbox file for __wakeup
     */
    protected $_filename;

    /**
     * modification date of mbox file for __wakeup
     */
    protected $_filemtime;

    /**
     * start and end position of messages as array(0 => start, 1 => end)
     */
    protected $_positions;


    /**
     * Count messages all messages in current box
     * Flags are not supported (exceptions is thrown)
     *
     * @param  int $flags           filter by flags
     * @throws Zend_Mail_Storage_Exception
     * @return int                  number of messages
     */
    public function countMessages($flags = null)
    {
        if ($flags) {
            throw new Zend_Mail_Storage_Exception('mbox does not support flags');
        }
        return count($this->_positions);
    }


    /**
     * Get a list of messages with number and size
     *
     * @param  int        $id  number of message
     * @return int|array      size of given message of list with all messages as array(num => size)
     */
    public function getSize($id = 0)
    {
        if ($id) {
            $pos = $this->_positions[$id - 1];
            return $pos['end'] - $pos['start'];
        }

        $result = array();
        foreach ($this->_positions as $num => $pos) {
            $result[$num + 1] = $pos['end'] - $pos['start'];
        }

        return $result;
    }


    /**
     * @param int $id number of message
     */
    private function _getPos($id)
    {
        if (!isset($this->_positions[$id - 1])) {
            throw new Zend_Mail_Storage_Exception('id does not exist');
        }

        return $this->_positions[$id - 1];
    }


    /**
     * Get a message with headers and body
     *
     * @param  int $id            number of message
     * @return Zend_Mail_Message
     * @throws Zend_Mail_Storage_Exception
     */
    public function getMessage($id)
    {
        $bodyLines = 0; // TODO: need a way to change that

        $message = $this->getRawHeader($id);
        // file pointer is after headers now
        if ($bodyLines) {
            $message .= "\n";
            while ($bodyLines-- && ftell($this->_fh) < $this->_positions[$id - 1]['end']) {
                $message .= fgets($this->_fh);
            }
        }

        return new Zend_Mail_Message(array('handler' => $this, 'id' => $id, 'headers' => $message));
    }

    /*
     * @throws Zend_Mail_Protocol_Exception
     */
    public function getRawHeader($id, $topLines = 0)
    {
        $messagePos = $this->_getPos($id);
        // TODO: toplines
        return stream_get_contents($this->_fh, $messagePos['separator'] - $messagePos['start'], $messagePos['start']);
    }

    /*
     * @throws Zend_Mail_Protocol_Exception
     */
    public function getRawContent($id)
    {
        $messagePos = $this->_getPos($id);
        return stream_get_contents($this->_fh, $messagePos['end'] - $messagePos['separator'], $messagePos['separator']);
    }

    /*
     * @throws Zend_Mail_Storage_Exception
     * @throws Zend_Mail_Protocol_Exception
     */
    public function getRawPart($id, $part)
    {
        // TODO: implement
        throw new Zend_Mail_Storage_Exception('not implemented');
    }

    /**
     * Create instance with parameters
     * Supported parameters are:
     *   - filename filename of mbox file
     *
     * @param  $params              array mail reader specific parameters
     * @throws Zend_Mail_Storage_Exception
     */
    public function __construct($params)
    {
        if (!isset($params['filename']) /* || Zend::isReadable($params['filename']) */) {
            throw new Zend_Mail_Storage_Exception('no valid filename given in params');
        }

        $this->_openMboxFile($params['filename']);
        $this->_has['top'] = true;
    }

    /**
     * check if given file is a mbox file
     *
     * if $file is a resource its file pointer is moved after the first line
     *
     * @param resource|string $file stream resource of name of file
     * @param bool $fileIsString file is string or resource
     * @return bool file is mbox file
     */
    protected function _isMboxFile($file, $fileIsString = true)
    {
        if ($fileIsString) {
            $file = @fopen($file, 'r');
            if (!$file) {
                return false;
            }
        } else {
            fseek($file, 0);
        }

        $result = false;

        $line = fgets($file);
        if (strpos($line, 'From ') === 0) {
            $result = true;
        }

        if ($fileIsString) {
            @fclose($file);
        }

        return $result;
    }

    /**
     * open given file as current mbox file
     *
     * @param string $filename filename of mbox file
     * @return null
     * @throws Zend_Mail_Storage_Exception
     */
    protected function _openMboxFile($filename)
    {
        if ($this->_fh) {
            $this->close();
        }

        $this->_fh = @fopen($filename, 'r');
        if (!$this->_fh) {
            throw new Zend_Mail_Storage_Exception('cannot open mbox file');
        }
        $this->_filename = $filename;
        $this->_filemtime = filemtime($this->_filename);

        if (!$this->_isMboxFile($this->_fh, false)) {
            @fclose($this->_fh);
            throw new Zend_Mail_Storage_Exception('file is not a valid mbox format');
        }

        $messagePos = array('start' => ftell($this->_fh), 'separator' => 0, 'end' => 0);
        while (($line = fgets($this->_fh)) !== false) {
            if (strpos($line, 'From ') === 0) {
                $messagePos['end'] = ftell($this->_fh) - strlen($line) - 2; // + newline
                if (!$messagePos['separator']) {
                    $messagePos['separator'] = $messagePos['end'];
                }
                $this->_positions[] = $messagePos;
                $messagePos = array('start' => ftell($this->_fh), 'separator' => 0, 'end' => 0);
            }
            if (!$messagePos['separator'] && !trim($line)) {
                $messagePos['separator'] = ftell($this->_fh);
            }
        }

        $messagePos['end'] = ftell($this->_fh);
        if (!$messagePos['separator']) {
            $messagePos['separator'] = $messagePos['end'];
        }
        $this->_positions[] = $messagePos;
    }

    /**
     * Close resource for mail lib. If you need to control, when the resource
     * is closed. Otherwise the destructor would call this.
     *
     * @return void
     */
    public function close()
    {
        @fclose($this->_fh);
        $this->_positions = array();
    }


    /**
     * Waste some CPU cycles doing nothing.
     *
     * @return void
     */
    public function noop()
    {
        return true;
    }


    /**
     * stub for not supported message deletion
     * @return null
     * @throws Zend_Mail_Storage_Exception
     */
    public function removeMessage($id)
    {
        throw new Zend_Mail_Storage_Exception('mbox is read-only');
    }

    /**
     * magic method for serialize()
     *
     * with this method you can cache the mbox class
     *
     * @return array name of variables
     */
    public function __sleep()
    {
        return array('_filename', '_positions', '_filemtime');
    }

    /**
     * magic method for unserialize()
     *
     * with this method you can cache the mbox class
     * for cache validation the mtime of the mbox file is used
     * @return null
     * @throws Zend_Mail_Storage_Exception
     */
    public function __wakeup()
    {
        if ($this->_filemtime != filemtime($this->_filename)) {
            $this->close();
            $this->_openMboxFile($this->_filename);
        } else {
            $this->_fh = @fopen($this->_filename, 'r');
            if (!$this->_fh) {
                throw new Zend_Mail_Storage_Exception('cannot open mbox file');
            }
        }
    }

}
