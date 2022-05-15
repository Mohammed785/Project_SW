<?php

    interface Entity{
        public function save();
        public function delete();
        public function update();
        public static function findByID($id);
        public static function findAll($criteria);
        public static function deleteByID($id);
        public static function deleteAll($criteria);
        public static function updateAll($criteria,$newData);
        public static function fromArray($data);
    }

?>