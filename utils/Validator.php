<?php
class Validator {
    public static function validateRegister($data) {
        if (empty($data['username']) || empty($data['password'])) {
            return 'Username and password are required';
        }
        if (strlen($data['username']) < 4) {
            return 'Username must be at least 4 characters';
        }
        if (strlen($data['password']) < 6) {
            return 'Password must be at least 6 characters';
        }
        return null;
    }

    public static function validateLogin($data) {
        if (empty($data['username']) || empty($data['password'])) {
            return 'Username and password are required';
        }
        return null;
    }
}