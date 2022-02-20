<?php

namespace App\Service;

use App\Security\User;

class BillingClient
{

    protected function sendRequest(string $url, ?array $parameters, ?string $token, ?string $method)
    {
        $ch = curl_init();


        curl_setopt($ch, CURLOPT_URL, $url);

        if ((str_contains($method, 'POST'))) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
            curl_setopt($ch, CURLOPT_POST, 1);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));

        if ($token) {
            $requestHeader = "Authorization: Bearer " . $token;
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $requestHeader
            ));
        }

        $result = curl_exec($ch);


        curl_close($ch);


        return $result;
    }
    public function login(string $login, string $password)
    {
        $apiToken = json_decode(
            $this->sendRequest(
                $_ENV["HOST_NAME_BILLING"] . '/api/v1/auth',
                ['username' => $login,
                    'password' => $password],
                null,
                'POST'
            ),
            true
        );


        if ($apiToken == null) {
            return null;
        }

        if (in_array('Invalid credentials.', $apiToken)) {
            return $apiToken;
        }


        $payload = $this->getPayload($apiToken['token']);

        $user = new User();
        $user->setRoles($payload->roles);
        $user->setApiToken($apiToken['token']);
        $user->setRefreshToken($apiToken['refresh_token']);


        return $user;
    }

    public function getBalanceToProfile(string $token)
    {
        $result = $this->sendRequest(
            $_ENV["HOST_NAME_BILLING"] . '/api/v1/current',
            null,
            $token,
            'POST'
        );
        return json_decode($result)->balance;
    }

    public function getTransactions(string $token)
    {

        $transactions = $this->sendRequest(
            $_ENV["HOST_NAME_BILLING"] . '/api/v1/transactions',
            null,
            $token,
            'GET'
        );
        return $transactions;
    }


    public function doSignup(string $email, string $password)
    {
        $apiToken = json_decode(
            $this->sendRequest(
                $_ENV["HOST_NAME_BILLING"] . '/api/v1/register',
                ['email' => $email, 'password' => $password],
                null,
                'POST'
            ),
            true
        );


        if (is_array($apiToken) && array_key_exists('error', $apiToken)) {
            return $apiToken;
        }

        if ($apiToken == null) {
            return null;
        }

        $payload = $this->getPayload($apiToken['token']);

        $user = new User();
        $user->setRoles($payload->roles);
        $user->setApiToken($apiToken['token']);
        $user->setRefreshToken($apiToken["refreshToken"]);

        return $user;
    }


    public function getCourses(string $token)
    {
        $courses = $this->sendRequest(
            $_ENV["HOST_NAME_BILLING"] . '/api/v1/courses',
            null,
            $token,
            'GET'
        );
        return $courses;
    }


    public function payCourse(string $token, string $code)
    {
        $response = $this->sendRequest(
            $_ENV["HOST_NAME_BILLING"] . '/api/v1/courses/' . $code . '/pay',
            null,
            $token,
            'POST'
        );

        return $response;
    }

    public function checkAvailableCourse(string $token, string $code)
    {
        $response = $this->sendRequest(
            $_ENV["HOST_NAME_BILLING"] .
            '/api/v1/transactions?filter[course]=' . $code .
            '&filter[skipexpired]=true&filter[type]=payment',
            null,
            $token,
            'GET'
        );

        return $response;
    }


    public function createCourse(string $token, array $params)
    {
        $response = json_decode(
            $this->sendRequest(
                $_ENV["HOST_NAME_BILLING"] . '/api/v1/courses',
                $params,
                $token,
                'POST'
            )
        );

        return $response;
    }


    public function editCourse(string $token, array $params, string $currentCode)
    {
        $response = json_decode($this->sendRequest(
            $_ENV["HOST_NAME_BILLING"] . '/api/v1/courses/' . $currentCode,
            $params,
            $token,
            'POST'
        ));
        return $response;
    }

    public function refreshToken(string $expiredToken)
    {
        $response = json_decode($this->sendRequest(
            $_ENV["HOST_NAME_BILLING"] . '/api/v1/token/refresh',
            ['refresh_token' => $expiredToken],
            null,
            'POST'
        ));

        return $response;
    }

    public function getPayload(string $token)
    {
        $tokenParts = explode(".", $token);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload);

        return $jwtPayload;
    }
}
