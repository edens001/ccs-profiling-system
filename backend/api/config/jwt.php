<?php
class JWT {
    private $secret_key = "your_secret_key_here_change_this_in_production";
    private $algorithm = 'HS256';

    public function generate($data) {
        $header = json_encode(['typ' => 'JWT', 'alg' => $this->algorithm]);
        $payload = json_encode(array_merge($data, [
            'iat' => time(),
            'exp' => time() + (86400 * 7) // 7 days expiration
        ]));

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', 
            $base64UrlHeader . "." . $base64UrlPayload, 
            $this->secret_key, 
            true
        );
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function validate($token) {
        $parts = explode('.', $token);
        if (count($parts) != 3) return false;

        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        if (!$payload || $payload['exp'] < time()) return false;

        $signature = $this->base64UrlDecode($parts[2]);
        $expectedSignature = hash_hmac('sha256', 
            $parts[0] . "." . $parts[1], 
            $this->secret_key, 
            true
        );

        return hash_equals($signature, $expectedSignature) ? $payload : false;
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
?>