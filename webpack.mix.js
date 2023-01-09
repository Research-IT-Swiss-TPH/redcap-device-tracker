let mix = require('laravel-mix');

mix
//.sass('src/style.scss', 'dist')
.js('src/tracker/appTracker.js', 'dist')
.js('src/monitor/appMonitor.js', 'dist')
.vue({ version: 2 });