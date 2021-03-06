<?php if (isset($error)) { ?>
    <div class="alert alert-danger"><?= $error ?></div>

<?php } if (isset($message)) { ?>
    <div class="alert alert-success"><?= $message ?></div>

<?php }

$user = new User;

if ($user->isLoggedIn()) { 
    // No need to show login form, direct 'em straight back to homepage 
    (new RedirectResponse('/'))->send();    

} else { 
    //--------------------- Login Box (snippet provided by Auth0) ---------------------------
    // https://auth0.com/docs/server-platforms/php#6-you-are-done-
    // After login, should be directed to \URL::to('/ccm/system/authentication/oauth2/google/attempt_auth')
    ?>

    <link rel="stylesheet" type="text/css" href="/application/authentication/auth0/login-screen.css">
    <div id="root" style="width: 280px; margin: 40px auto; padding: 10px; border-style: dashed; border-width: 1px;">
    embeded area
    </div>
    <script src="https://cdn.auth0.com/js/lock-8.1.min.js"></script>
    <script>      
      var lock = new Auth0Lock('kB2fHUrgh60ntMKN18Kxi403m58rQDOI', 'yump.au.auth0.com');      
      
      lock.show({
          container: 'root'
        , callbackURL: '<?= \URL::to('/ccm/system/authentication/oauth2/auth0/callback') ?>'
        , responseType: 'code'
        , authParams: {
          scope: 'openid profile'
        }
      });
    </script>
    
<?php } ?>
