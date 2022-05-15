<?php
include_once "./baseEntity.php";
class Post implements Entity{
    private $id,$user_id;
    public $body;

    function __construct($post_info){
        $this->id = isset($post_info["id"])?$post_info["id"]:0;
        $this->body = $post_info["body"];
        $this->user_id = $post_info["user_id"];
    }
    function getId(){
        return $this->id;
    }
    function getAuthorId(){
        return $this->user_id;
    }
    function save(){
        if($this->id==0){
            return $this->db->insert("INSERT INTO posts(body,user_id) VALUES('$this->body','$this->user_id')");
        }
        return $this->update();
    }
    function update(){
        if($this->id!=0){
            return $this->db->update("UPDATE posts SET body = '$this->body' WHERE id='$this->id'");

        }
        return $this->save();
    }
    function delete(){
        if($this->id!=0){
            return $this->db->delete("DELETE * FROM posts WHERE id='$this->id'");
        }
        echo "Cannot Delete. Record Is Not Saved In DB.";
        return false;
    }

    function UserSavePost($user_id){
        $saved = $this->db->insert("INSERT INTO saved_posts(post_id,user_id)VALUES({$this->id},{$user_id})");
        return $saved;
    }
    function postReact($user_id,$liked=1){
        if(!$this->id){
            $this->save();
        }
        $like = $this->db->insert("INSERT INTO post_reacts(user_id,post_id,liked) VALUES({$user_id},{$this->id},{$liked})");
        return $like;
    }

    function postCommentReact($user_id,$comment_id,$liked=1){
        $like = $this->db->insert("INSERT INTO comment_reacts(user_id,comment_id,liked) VALUES({$user_id},{$comment_id},{$liked})");
        return $like;
    }

    function getPostComments(){
        if(!$this->id){
            $this->save();
        }
        $comments = $this->db->select("SELECT * FROM comments WHERE post_id={$this->id}");
        return $comments;
    }

    static function getUserSavedPosts($user_id){
        $db = getDBConnection();
        $posts = $db->select("SELECT * FROM posts JOIN saved_posts sv WHERE sv.post_id=posts.id AND sv.user_id = {$user_id}");
        return $posts;
    }

    static function findByID($id){
        $db = getDBConnection();
        $post = $db->select("SELECT * FROM posts WHERE id={$id}");
        if($post){
            return new Post($post[0]);
        }else{
            return NULL;
        }
    }

    static function findAll($criteria){
        $db = getDBConnection();
        $criteria = createCriteria($criteria);
        $posts_data = $db->select("SELECT * FROM posts WHERE {$criteria}");
        $posts = array();
        foreach ($posts_data as $post) {
            array_push($posts,new Post($post));
        }
        return $posts;
    }

    static function deleteByID($id){
        $db = getDBConnection();
        $deleted = $db->delete("DELETE FROM posts WHERE id='$id'");
        return $deleted;
    }

    static function deleteAll($criteria){
        $db = getDBConnection();
        $criteria = createCriteria($criteria);
        $deleted = $db->delete("DELETE FROM posts WHERE {$criteria}");
        return $deleted;
    }

    static function updateAll($criteria,$newData){
        $db = getDBConnection();
        $criteria = createCriteria($criteria);
        $newData  = createCriteria($newData,",");
        $updated = $db->update("UPDATE posts SET {$newData} WHERE {$criteria}");
        return $updated;
    }

    static function fromArray($data){
        $posts = array();
        foreach($data as $post){
            array_push($posts,new Post($post));
        }
        return $posts;
    }
}


?>