<?php

namespace App\Repositories\Custom\Accounts;

use App\Repositories\Util\LogRepository;
use Exception;

use App\Repositories\Custom\AccountsCustom;
use JWTAuth;


class AuthenticateCustom {

    public function __construct() {
        return $this;
    }

    public function model() {
        return \App\Models\Accounts::class;
    }

    /**
     * Validate
     *
     * @param Array($mixed) $params Associative array of parameter
     * @param String $params["login"] The user email
     * @param String $params["password"] The user secret
     * @return Array(mixed) The result informations
     */
    public function validate($param) {

        try {
            if (!is_array($param)) {
                throw new Exception("Expected array as parameter , " . (is_object($param) ? get_class($param) : gettype($param)) . " found.");
            }

            if (array_key_exists('email', $param)){
				if (!is_string($param['email'])) {
					$result = array("code" => 4000, "description" => "email is a string");
					echo json_encode($result, JSON_UNESCAPED_SLASHES);
					return false;
				}
			}else {
                throw new Exception("Expected 'email' in array as parameter , " . (is_object($param['email']) ? get_class($param['email']) : gettype($param['email'])) . " found.");
            }

            if (array_key_exists('password', $param)) {
				if (!is_string($param['password'])) {
					$result = array("code" => 4000, "description" => "password is a string");
					echo json_encode($result, JSON_UNESCAPED_SLASHES);
					return false;
				}
			}else{
                throw new Exception("Expected 'password' in array as parameter , " . (is_object($param['password']) ? get_class($param['password']) : gettype($param['password'])) . " found.");
            }                                   
            return TRUE;
        } catch (Exception $ex) {
            LogRepository::printLog('error', $ex->getMessage());
        }
    }

    /**
     * Format of the response
     * 
     * @param array $param
     * @return array
     */
    public function prepare_reponse_after_post($account, $token) {
//        dd($token);
        try {
            $result = array();
            $validity = config('jwt.ttl');
            $result = array(
                'code' => 200,
                'account_id' => $account->id,
                'token' => $token,
                'validity' => $validity,
            );

            return $result;
        } catch (Exception $ex) {
            LogRepository::printLog('error', $ex->getMessage());
        }
    }
   /**
     * Authenticate
     *
     * @param Array($mixed) $params Associative array of parameter
     * @param String $params["login"] The user email
     * @param String $params["password"] The user secret
     * @return Array(mixed) The result informations
     */
    public function authenticate($params) {
         if (!is_array($params))
                throw new Exception("Expected Array as parameter, " . (is_object($params) ? get_class($params) : gettype($params)) . ' given.');

            if (!array_key_exists("email", $params))
                throw new Exception("Expected key (login) in parameter array.");

            if (!is_string($params["email"]))
                throw new Exception("Expected String for key (email) in parameter array , " . (is_object($params["email"]) ? get_class($params["email"]) : gettype($params["email"])) . " found.");

            if (!array_key_exists("password", $params))
                throw new Exception("Expected key (password) in parameter array.");

            if (!is_string($params["password"]))
                throw new Exception("Expected String for key (password) in parameter array , " . (is_object($params["password"]) ? get_class($params["password"]) : gettype($params["password"])) . " found.");


        $this->validate($params);
        $custom_account = new AccountsCustom();
        $account = $custom_account->checkUser($params['email']);

        if (!is_null($account)) {
            if (!$token = JWTAuth::attempt($params)) {
				LogRepository::printLog('error', "Invalid attempt to authenticate an account with inputs {" . var_export($params,true) . "}.");
                $result = array("code" => 4002, "error" => 'Authentication failed');
                return response()->json($result, 400);
            }
			$account->is_active = 1;
			$account->date_updated = date('Y-m-d H:i:s');
			$account->update();
            LogRepository::printLog('info', "Successful authentication of account #{" . $account . "}.");
            $result = $this->prepare_reponse_after_post($account, $token);
            return $result;
        } else {
            LogRepository::printLog('error', "Invalid authentication attempt with inputs:  {". var_export($params,true)."}");
            $result = array("code" => 400,"description" => 'Invalid credentials');
            return response()->json($result, 400);
        }
        
    }

}
