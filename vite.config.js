import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel(
            [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/party.js",
                "resources/js/partyPlayer.js",
                "resources/js/marquee-text-element.js"
            ]
        ),
    ],
});
