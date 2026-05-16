<?php
// =============================================
// api/auth_api.php — Autenticação JWT simples para a API REST
// =============================================
// Endpoint de login: POST /api/login.php
// Retorna: { "token": "eyJ..." }
// Usar nas demais chamadas: Header "Authorization: Bearer eyJ..."
// =============================================

define('JWT_SECRET', 'fisioterap_jwt_secret_2025_altere_em_producao');
define('JWT_EXPIRY', 3600); // 1 hora

/**
 * Gera um token JWT simples (HS256).
 */
function gerarJwt(int $userId, string $nome): string {
    $header  = base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64url(json_encode([
        'sub'  => $userId,
        'nome' => $nome,
        'iat'  => time(),
        'exp'  => time() + JWT_EXPIRY,
    ]));
    $sig = base64url(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$sig";
}

/**
 * Valida o token JWT da requisição.
 * Lança exceção se inválido ou expirado.
 */
function autenticar(): array {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
        resposta(401, ['erro' => 'Token ausente. Envie: Authorization: Bearer <token>']);
    }

    $parts = explode('.', $m[1]);
    if (count($parts) !== 3) resposta(401, ['erro' => 'Token malformado.']);

    [$header, $payload, $sig] = $parts;
    $expectedSig = base64url(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));

    if (!hash_equals($expectedSig, $sig)) resposta(401, ['erro' => 'Assinatura inválida.']);

    $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    if (time() > ($data['exp'] ?? 0)) resposta(401, ['erro' => 'Token expirado. Faça login novamente.']);

    return $data;
}

function base64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
