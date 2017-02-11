<?php

namespace BfwMailer\modeles;

/**
 * Abstract class that carries email data
 * @author Alexandre Moittié <contact@alexandre-moittie.com>
 * @package bfw-mailer
 * @version 1.0
 */
abstract class AbstrEmailData extends AbstrModeles
{
    
    // DB constants (DB map)
    const DB_ID          = 'id';
    const DB_LAST_ACT    = 'last_action_ts';
    
    
    
    /**
     * Retrieve data
     * 
     * @param integer|null $id : content id to search
     * @return mixed : data searched in case of success, false otherwise
     */
    public function retrieve($id = null)
    {
        if ($id === null) {
            $req = $this->select()->from($this->tableName);
            $result = $this->fetchSql($req);
        }
        
        else {
            $req = $this->select()->from($this->tableName)->where(self::DB_ID.'=:id', array('id' => $id));
            $result = $this->fetchSql($req, 'fetchRow');
        }
        
        return $result;
    }
    
    
    
    /**
     * Update data last action timestamp
     * 
     * @param integer $id       : data id 
     * @param integer $last_act : last action timestamp
     * 
     * @throws \Exception
     * @return boolean : true in case of success, false otherwise
     */
    public function updateLastAction ($id, $last_act = null) 
    {
        // if last action timestamp is not filled, take actual timestamp
        if ($last_act === null || empty($last_act)) {
            $last_act = strval(time());
        }

        else {
            $last_act = strval($last_act);
        }
        
        $req = $this->select()->from($this->tableName)->where(self::DB_ID.'=:id', array('id' => $id));
        $result = $this->fetchSql($req, 'fetchRow');

        if (!empty($result) && $result[self::DB_LAST_ACT] !== $last_act) {
            $req = $this->update($this->tableName, array(self::DB_LAST_ACT => $last_act))
                    ->where(self::DB_ID.'=:id', array(':id' => $id))
                    ->execute();


            if ($req === false) {
                throw new \Exception('last action update failed in table '.$this->tableName.' for id '.$id);
            }
        }
    }
    
    
    
    /**
     * flush content regarding its last action timestamp
     */
    abstract public function flush($timestamp);
    
    
    
    /**
     * Remove data
     * 
     * @param integer $id : data id into the database
     * @throws \Exception
     */
    public function remove($id)
    {
        // Delete data from table if last action was performed before $timestamp limit
        $req = $this->delete($this->tableName)
                ->where(self::DB_ID.'=:id', array('id' => $id))
                ->execute();

        if ($req === false) {
            throw new \Exception('removing failed in table '.$this->tableName.' for id '.$outbox_id);
        }
    }
    
}
