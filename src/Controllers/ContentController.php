<?php

declare(strict_types=1);

namespace Fulcrum\Controllers;

use Fulcrum\Database;
use Twig\Environment;

final class ContentController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Database    $db,
    ) {}

    public function index(): string
    {
        $items = $this->db
            ->query("
                SELECT id, type, title, slug, status, created_at
                FROM content
                ORDER BY created_at DESC
            ")
            ->fetchAll();

        return $this->twig->render('admin/content/index.html.twig', [
            'active'  => 'content',
            'items'   => $items,
        ]);
    }

    public function create(): string
    {
        return $this->twig->render('admin/content/create.html.twig', [
            'active' => 'content',
        ]);
    }

    public function store(): void
    {
        $title  = trim($_POST['title']  ?? '');
        $body   = trim($_POST['body']   ?? '');
        $type   = trim($_POST['type']   ?? 'post');
        $status = trim($_POST['status'] ?? 'draft');
        $slug   = $this->generateSlug($title);

        if ($title === '') {
            // For now redirect back with no error message
            // We add flash messages later
            header('Location: /_fulcrum/content/create');
            exit;
        }

        $stmt = $this->db->prepare("
            INSERT INTO content (type, title, slug, body, status)
            VALUES (:type, :title, :slug, :body, :status)
        ");

        $stmt->execute([
            'type'   => $type,
            'title'  => $title,
            'slug'   => $slug,
            'body'   => $body,
            'status' => $status,
        ]);

        header('Location: /_fulcrum/content');
        exit;
    }

    public function edit(string $id): string
    {
        $stmt = $this->db->prepare("
            SELECT * FROM content WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();

        if (!$item) {
            http_response_code(404);
            return $this->twig->render('errors/404.html.twig');
        }

        return $this->twig->render('admin/content/edit.html.twig', [
            'active' => 'content',
            'item'   => $item,
        ]);
    }

    public function update(string $id): void
    {
        $title  = trim($_POST['title']  ?? '');
        $body   = trim($_POST['body']   ?? '');
        $status = trim($_POST['status'] ?? 'draft');

        if ($title === '') {
            header("Location: /_fulcrum/content/{$id}/edit");
            exit;
        }

        $stmt = $this->db->prepare("
            UPDATE content
            SET title      = :title,
                body       = :body,
                status     = :status,
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            'title'  => $title,
            'body'   => $body,
            'status' => $status,
            'id'     => $id,
        ]);

        header('Location: /_fulcrum/content');
        exit;
    }

    public function delete(string $id): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM content WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);

        header('Location: /_fulcrum/content');
        exit;
    }

    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Check for duplicates and append a number if needed
        $original = $slug;
        $count    = 1;

        while ($this->slugExists($slug)) {
            $slug = $original . '-' . $count;
            $count++;
        }

        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM content WHERE slug = :slug
        ");
        $stmt->execute(['slug' => $slug]);
        return (int) $stmt->fetchColumn() > 0;
    }
}