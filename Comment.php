<?php
include_once "./baseEntity.php";

class Comment implements Entity{
    private $id,$user_id,$post_id;
    public $body;

    function __construct($comment_info){
        $this->id = isset($comment_info["id"])?$comment_info["id"]:0;
        $this->body = $comment_info["body"];
        $this->user_id = $comment_info["user_id"];
        $this->post_id = $comment_info["post_id"];
        
    }
    function getId(){
        return $this->id;
    }
    function getAuthorId(){
        return $this->user_id;
    }
    function getPostId(){
        return $this->post_id;
    }
    function save(){
        if($this->id==0){
            return $this->db->insert("INSERT INTO comments(body,post_id,user_id) VALUES('$this->body','$this->post_id','$this->user_id')");
        }
        return $this->update();
    }
    function update(){
        if($this->id!=0){
            return $this->db->update("UPDATE comments SET body = '$this->body' WHERE id='$this->id'");

        }
        return $this->save();
    }
    function delete(){
        if($this->id!=0){
            return $this->db->delete("DELETE * FROM comments WHERE id='$this->id'");
        }
        echo "Cannot Delete. Record Is Not Saved In DB.";
        return false;
    }

    function CommentReact($user_id,$post_id,$liked=1){
        if(!$this->id){
            $this->save();
        }
        $exists = $this->db->select("SELECT * FROM comment_reacts user_id={$user_id} AND post_id={$post_id}");
        if($exists){
            if($liked!=$exists["liked"]){
                return $this->db->update("UPDATE comment_reacts SET liked={$liked} WHERE user_id={$user_id} AND post_id={$post_id}");
            }
            return $this->db->delete("DELETE * FROM comment_reacts WHERE user_id={$user_id} AND post_id={$post_id}");
        }
        $like = $this->db->insert("INSERT INTO post_reacts(user_id,post_id,liked) VALUES({$user_id},{$post_id},{$liked})");
        return $like;
    }

    function getPostComments(){
        if(!$this->id){
            $this->save();
        }
        $comments = $this->db->select("SELECT * FROM comments WHERE post_id={$this->id}");
        return $comments;
    }

    static function findByID($id){
        $db = getDBConnection();
        $comment = $db->select("SELECT * FROM comments WHERE id={$id}");
        if($comment){
            return new Comment($comment[0]);
        }else{
            return NULL;
        }
    }

    static function findAll($criteria){
        $db = getDBConnection();
        $criteria = createCriteria($criteria);
        $comments_data = $db->select("SELECT * FROM comments WHERE {$criteria}");
        $comments = array();
        foreach ($comments_data as $comment) {
            array_push($comments,new Comment($comment));
        }
        return $comments;
    }

    static function deleteByID($id){
        $db = getDBConnection();
        $deleted = $db->delete("DELETE FROM comments WHERE id='$id'");
        return $deleted;
    }

    static function deleteAll($criteria){
        $db = getDBConnection();
        $criteria = createCriteria($criteria);
        $deleted = $db->delete("DELETE FROM comments WHERE {$criteria}");
        return $deleted;
    }

    static function updateAll($criteria,$newData){
        $db = getDBConnection();
        $criteria = createCriteria($criteria);
        $newData  = createCriteria($newData,",");
        $updated = $db->update("UPDATE comments SET {$newData} WHERE {$criteria}");
        return $updated;
    }

    static function fromArray($data){
        $comments = array();
        foreach($data as $comment){
            array_push($comments,new Comment($comment));
        }
        return $comments;
    }
}


?>