<?php

declare(strict_types=1);

namespace Fulcrum\Controllers;

use Fulcrum\Database;
use Twig\Environment;

final class DashboardController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Database    $db,
    ) {}

    public function index(): string
    {
        $contentCount = $this->db
            ->query("SELECT COUNT(*) FROM content")
            ->fetchColumn();

        $postCount = $this->db
            ->query("SELECT COUNT(*) FROM content WHERE type = 'post'")
            ->fetchColumn();

        return $this->twig->render('admin/dashboard/index.html.twig', [
            'active'        => 'dashboard',
            'post_count'    => $postCount,
            'content_count' => $contentCount,
        ]);
    }
}