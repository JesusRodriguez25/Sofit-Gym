<?php

namespace App\Controllers;

use App\Core\Route;
use App\Services\Auth\UserSession;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Tools;
use App\Models\Notificacion;
use App\Models\NotificacionModel;

$notifModel = new NotificacionModel();
$user = UserSession::get();

if (!$user) {
    return Response::json(["error" => "No autenticado"], 401);
}

switch (Route::action()) {
    case "query":
        $id_usuario = Request::queryInt("id") ?? $user->id;
        $results = $notifModel->query((int) $id_usuario);
        return Response::json($results);

    case "find":
        $id = Request::queryInt("id") ?? 0;
        $data = $notifModel->find((int) $user->id, $id);

        return $data
            ? Response::json($data)
            : Response::noContent();

    case "leido":
        $id = Request::queryInt("id") ?? 0;
        $leido = Request::queryBool("leido") ?? true;

        $notifModel->setLeido((int) $user->id, $id, $leido);
        return Response::noContent();

    case "leerTodas":
        $notifModel->setLeidoTodas((int) $user->id);
        return Response::noContent();

    case "sendAll":
        $body = Request::getParsedBody();
        $data = Tools::map(Notificacion::class, $body);
        $total = $notifModel->sendToAll($data);

        return Response::json([
            "success" => true,
            "enviadas" => $total,
        ]);

    case "sendMultiple":
        $body = Request::getParsedBody();
        $data = Tools::map(Notificacion::class, $body);

        $enviarATodos = !empty($body["todos"]) || ($body["id_usuarios"] ?? null) === "all";

        if ($enviarATodos) {
            $total = $notifModel->sendToAll($data);
            return Response::json([
                "success" => true,
                "enviadas" => $total,
            ]);
        }

        $id_usuarios = $body["id_usuarios"] ?? [$user->id];
        if (!is_array($id_usuarios) || count($id_usuarios) === 0) {
            $id_usuarios = [$user->id];
        }

        $notifModel->sendByUsuarios($id_usuarios, notificacion: $data);
        return Response::noContent();
}