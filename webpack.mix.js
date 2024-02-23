let mix = require('laravel-mix');

mix
.js('src/tracker/appTracker.js', 'dist')
.js('src/monitor/appMonitor.js', 'dist')
.sourceMaps()
.vue({ version: 2 });