// webpack.config.js
require("popper.js")
var Encore = require('@symfony/webpack-encore');
var $ = require("jquery");

global.$ = global.jQuery = $;

Encore
    // каталог проекта , где будут храниться все скомпилированные ресурсы
    .setOutputPath('public/build/')

    // публичный путь, используемый веб-сервером для доступа к предыдущему каталогу
    .setPublicPath('/build')
    
    // создаст public/build/app.js и public/build/app.css
    .addEntry('app', './assets/app.js')

    // позволит обработку файлов sass/scss
    .enableSassLoader()

    // позволить приложениям наследования использовать $/jQuery в качестве глобальной переменной
    .autoProvidejQuery()

    .enableSingleRuntimeChunk()

    .enableSourceMaps(!Encore.isProduction())

    // очистить outputPath dir перед каждым построением
    .cleanupOutputBeforeBuild()
    

    // создать хешированные имена файлов (например, app.abc123.css)
    // .enableVersioning()
    
  
// экспортировать финальную конфигурацию
module.exports = Encore.getWebpackConfig();
