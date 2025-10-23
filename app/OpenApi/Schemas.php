<?php
/**
 * @OA\Schema(
 *   schema="Message",
 *   @OA\Property(property="id", type="integer", example=101),
 *   @OA\Property(property="user_id", type="integer", example=1),
 *   @OA\Property(property="service", type="string", example="slack"),
 *   @OA\Property(property="status",  type="string", example="success"),
 *   @OA\Property(property="text",    type="string", example="Hello team!"),
 *   @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */