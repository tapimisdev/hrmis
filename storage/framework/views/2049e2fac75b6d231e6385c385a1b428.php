<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title'); ?></title>

    <!-- Vite (compiles and loads local SCSS/JS assets) -->
    <?php echo app('Illuminate\Foundation\Vite')([
        'resources/sass/errors.scss',
    ]); ?>
</head>
<body>
    <div class="container-fluid">
        <?php echo $__env->yieldContent('content'); ?>
    </div>
</body>
</html><?php /**PATH /var/www/html/resources/views/errors/layout.blade.php ENDPATH**/ ?>