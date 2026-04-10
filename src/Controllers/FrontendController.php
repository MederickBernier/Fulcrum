<?php

declare(strict_types=1);

namespace Fulcrum\Controllers;

use Fulcrum\Database;
use Twig\Environment;

final class FrontendController{
    public function __construct(
        private readonly Environment $twig,
        private readonly Database    $db
    ){}

    public function home(): string{
        $posts = $this->db
            ->query("
                SELECT      id, type, title, slug, body, created_at
                FROM        content
                WHERE       status = 'published'
                AND         type = 'post'
                ORDER BY    created_at DESC
            ")
            ->fetchAll();

        return $this->twig->render('frontend/home.html.twig', [
            'posts' => $posts
        ]);
    }

    public function show(string $slug):string{
        $stmt = $this->db->prepare("
            SELECT  id, type, title, slug, body, created_at, updated_at
            FROM    content
            WHERE   slug = :slug
            AND     status = 'published'
        ");
        $stmt->execute([
            'slug' => $slug
        ]);
        $item = $stmt->fetch();

        if(!$item){
            http_response_code(404);
            return $this->twig->render('errors/404.html.twig');
        }

        return $this->twig->render('frontend/show.html.twig',[
            'item' => $item
        ]);
    }
}