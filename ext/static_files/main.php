<?php declare(strict_types=1);

class StaticFiles extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        global $config, $page;
        // hax.
        if ($page->mode == PageMode::PAGE && (!isset($page->blocks) || $this->count_main($page->blocks) == 0)) {
            $h_pagename = html_escape(implode('/', $event->args));
            $f_pagename = preg_replace("/[^a-z_\-\.]+/", "_", $h_pagename);
            $theme_name = $config->get_string(SetupConfig::THEME, "default");

            $theme_file = "themes/$theme_name/static/$f_pagename";
            $static_file = "ext/static_files/static/$f_pagename";

            if (file_exists($theme_file) || file_exists($static_file)) {
                $filename = file_exists($theme_file) ? $theme_file : $static_file;

                $page->add_http_header("Cache-control: public, max-age=600");
                $page->add_http_header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 600) . ' GMT');
                $page->set_mode(PageMode::DATA);
                $page->set_data(file_get_contents($filename));
                if (endsWith($filename, ".ico")) {
                    $page->set_type("image/x-icon");
                }
                if (endsWith($filename, ".png")) {
                    $page->set_type("image/png");
                }
                if (endsWith($filename, ".txt")) {
                    $page->set_type("text/plain");
                }
            }
        }
    }

    private function count_main($blocks)
    {
        $n = 0;
        foreach ($blocks as $block) {
            if ($block->section == "main" && $block->is_content) {
                $n++;
            } // more hax.
        }
        return $n;
    }

    public function get_priority(): int
    {
        return 98;
    }  // before 404
}
