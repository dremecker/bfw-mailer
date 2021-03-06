<?php

namespace BfwMailer\modeles;

/**
 * Class that carries email content data
 * @author Aetiom <aetiom@protonmail.com>
 * @package bfw-mailer
 * @version 1.1
 */
class Content extends AbstrEmailData
{
    
    // DB constants (DB map)
    const DB_SUBJECT     = 'subject';
    const DB_BODY        = 'body';
    const DB_ALT_BODY    = 'alt_body';
    const DB_ATTACHMENTS = 'attachments';

    /**
     * @var string $tableName : table name
     */
    protected $tableName = 'bfwmailer_content';
    
    
    
    /**
     * Create table if it doesn't already exist
     * @param string $table_map_query : table map query to insert into the full query for table creation process
     */
    public function create_table($table_map_query = null) 
    {
        // prepare create table request
        $table_map_query = self::DB_ID.     " ID_PRIMARY_KEY_AI, "
            .self::DB_SUBJECT.     " VARCHAR(96), "
            .self::DB_BODY.        " TEXT, "
            .self::DB_ALT_BODY.    " TEXT, "
            .self::DB_ATTACHMENTS. " TEXT, "
            .self::DB_LAST_ACT.    " INTEGER";
        
        parent::create_table($table_map_query);
    }

    
    
    /**
     * Search email content into the database
     * 
     * @param string $subject     : subject
     * @param string $body        : main body (html or text)
     * @param string $alt_body    : alternative body (text)
     * @param string $attachments : attached pieces
     * @return mixed : content id in case of success, false otherwise
     */
    public function search($subject, $body, $alt_body, $attachments)
    {
        $req = $this->select()->from($this->tableName, '*')
                ->where(self::DB_SUBJECT.    '=:sub',  array(':sub'  => $this->secureData($subject)))
                ->where(self::DB_BODY.       '=:body', array(':body' => $this->secureData($body, '', true)))
                ->where(self::DB_ALT_BODY.   '=:altb', array(':altb' => $this->secureData($alt_body)))
                ->where(self::DB_ATTACHMENTS.'=:att',  array(':att'  => $this->secureData($attachments)))
                ->limit(1);
        $result = $this->fetch_sql($req, 'fetchRow');
        
        if (isset($result[self::DB_ID])) {
            return $result[self::DB_ID];
        }
        
        return false;
    }
    
    
    
    /**
     * Add content into the database
     * 
     * @param string $subject     : subject
     * @param string $body        : main body (html or text)
     * @param string $alt_body    : alternative body (text)
     * @param string $attachments : attached pieces
     * @return mixed : content id in case of success, false otherwise
     */
    public function add($subject, $body, $alt_body, $attachments)
    {
        $content = array(
            self::DB_SUBJECT     => $this->secureData($subject),
            self::DB_BODY        => $this->secureData($body, '', true),
            self::DB_ALT_BODY    => $this->secureData($alt_body),
            self::DB_ATTACHMENTS => $this->secureData($attachments)
        );
        
        $req = $this->insert()->into($this->tableName, $content)->execute();
        
        if (!empty($req)) {
            return $this->obtainLastInsertedId();
        }
    }
    
    
    
    /**
     * Flush content regarding its last action timestamp, 
     * do not remove content that is actualy used by any outbox email.
     * Method will insert()->into all deprecated contents that is older than timestamp.
     * 
     * @param integer $timestamp : timestamp limit
     */
    public function flush($timestamp) 
    {
        if (!$this->is_flush_needed($timestamp)) {
            return false;
        }
        
        $outbox = new Outbox();
        
        $req = $this->select()->from($this->tableName, '*')
                ->where(self::DB_LAST_ACT.'<=:limit', array(':limit' => $timestamp));
        $result = $this->fetch_sql($req);
        
        if (!empty($result)) {
            foreach ($result as $line) {
                
                if ($outbox->is_content_used($line[self::DB_ID]) === false) {
                    $this->remove($line[self::DB_ID]);
                }
            }
        } else {
            return false;
        }
        
        return true;
    }

}
