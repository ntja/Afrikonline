<?php

namespace App\Repositories\Custom\Resource\Accounts;

class Account {

    public $id;

    public function __construct($id = null) {
        if($id){
        	$this->id = $id;
        }
        return $this;
    }

}
