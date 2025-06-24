<?php
// C:/xampp/htdocs/Web_Project/Modules/Utils/HelperFunctions.php

class HelperFunctions {
    /**
     * Generates a random password.
     *
     * @param int $length The desired length of the password.
     * @return string The generated random password.
     */
    public static function generateRandomPassword($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()-_=+';
        $password = '';
        $charactersLength = strlen($characters);
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, $charactersLength - 1)];
        }
        return $password;
    }
}