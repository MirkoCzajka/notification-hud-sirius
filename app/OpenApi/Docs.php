<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *   title="Notification HUD API",
 *   version="1.0.0",
 *   description="API docs"
 * )
 *
 * @OA\Server(
 *   url="/",
 *   description="Base server"
 * )
 */
class Docs {}

/**
 * Healthcheck
 * @OA\Get(
 *   path="/api/_ping",
 *   tags={"Health"},
 *   summary="Ping de salud",
 *   @OA\Response(response=200, description="OK")
 * )
 */
class HealthPing {}
