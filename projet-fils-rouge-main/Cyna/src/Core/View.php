<?php

declare(strict_types=1);

namespace Cyna\Core;

use RuntimeException;

/**
 * Moteur de gabarits PHP natif avec héritage de layout et sections.
 *
 * Un gabarit déclare son layout via $this->extends('layouts/front') puis
 * remplit des sections ($this->section('styles') ... $this->endSection()).
 * Le layout récupère le corps principal via $content et les sections via
 * $this->yieldSection('styles').
 */
final class View
{
    private string $viewPath;

    /** @var array<string,string> Contenu des sections rendues. */
    private array $sections = [];

    private ?string $layout = null;

    /** @var array<string,mixed> */
    private array $layoutData = [];

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = $viewPath ?? BASE_PATH . '/resources/views';
    }

    /** @param array<string,mixed> $data */
    public function render(string $template, array $data = []): string
    {
        $content = $this->renderFile($template, $data);

        if ($this->layout !== null) {
            $layout = $this->layout;
            $this->layout = null;
            $content = $this->renderFile($layout, array_merge($data, $this->layoutData, ['content' => $content]));
        }

        return $content;
    }

    /** @param array<string,mixed> $data */
    private function renderFile(string $template, array $data): string
    {
        $file = $this->viewPath . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Gabarit introuvable : {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $file;

        return (string) ob_get_clean();
    }

    /** Déclare le layout parent du gabarit courant. @param array<string,mixed> $data */
    public function extends(string $layout, array $data = []): void
    {
        $this->layout = $layout;
        $this->layoutData = $data;
    }

    /** Inclut un sous-gabarit (partial) à l'emplacement courant. @param array<string,mixed> $data */
    public function insert(string $template, array $data = []): void
    {
        echo $this->renderFile($template, $data);
    }

    public function section(string $name): void
    {
        $this->sections['_current'] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $name = $this->sections['_current'] ?? '';
        unset($this->sections['_current']);
        $this->sections[$name] = (string) ob_get_clean();
    }

    public function yieldSection(string $name): string
    {
        return $this->sections[$name] ?? '';
    }
}
