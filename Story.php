<?php
include_once "./baseEntity.php";
include_once "./utils.php";

class Story implements Entity{
    private $id,$author_id;
    public $body;

    function __construct($story_info){
        $this->id = isset($story_info["id"])?$story_info["id"]:0;
        $this->body = $story_info["body"];
        $this->author_id = $story_info["author_id"];
        $this->db = getDBConnection();
    }
    function getId(){
        return $this->id;
    }
    function getAuthorId(){
        return $this->author_id;
    }
    function save(){
        if($this->id==0){
            return $this->db->insert("INSERT INTO storys(body,author_id) VALUES('$this->body','$this->author_id')");
        }
        return $this->update();
    }
    function update(){
        if($this->id!=0){
            return $this->db->update("UPDATE storys SET body = '$this->body' WHERE id='$this->id'");

        }
        return $this->save();
    }
    function delete(){
        if($this->id!=0){
            return $this->db->delete("DELETE * FROM storys WHERE id='$this->id'");
        }
        echo "Cannot Delete. Record Is Not Saved In DB.";
        return false;
    }

    function createStoryView($user_id){
        $exists = $this->db->select("SELECT * FROM story_views WHERE story_id={$this->id} AND user_id={$user_id}");
        if($exists){
            return false;
        }
        return $this->db->insert("INSERT INTO story_views(user_id,story_id) VALUES({$user_id},{$this->id})");
    }

    function getStoryViews(){
        return $this->db->select("SELECT * FROM story_views WHERE story_id={$this->id}");
    }
    static function findByID($id){
        $db = getDBConnection();
        $story = $db->select("SELECT * FROM storys WHERE id={$id}");
        if($story){
            return new Story($story[0]);
        }else{
            return NULL;
        }
    }

    static function findAll($criteria){
        $db = getDBConnection();
        $criteria = createCriteria($criteria);
        $storys_data = $db->select("SELECT * FROM storys WHERE {$criteria}");
        $storys = array();
        foreach ($storys_data as $story) {
            array_push($storys,new Story($story));
        }
        return $storys;
    }

    static function deleteByID($id){
        $db = getDBConnection();
        $deleted = $db->delete("DELETE FROM storys WHERE id='$id'");
        return $deleted;
    }

    static function deleteAll($criteria){
        $db = getDBConnection();
        $criteria = createCriteria($criteria);
        $deleted = $db->delete("DELETE FROM storys WHERE {$criteria}");
        return $deleted;
    }

    static function updateAll($criteria,$newData){
        $db = getDBConnection();
        $criteria = createCriteria($criteria);
        $newData  = createCriteria($newData,",");
        $updated = $db->update("UPDATE storys SET {$newData} WHERE {$criteria}");
        return $updated;
    }

    static function fromArray($data){
        $storys = array();
        foreach($data as $story){
            array_push($storys,new Story($story));
        }
        return $storys;
    }
}


?>