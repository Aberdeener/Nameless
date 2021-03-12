<?php
if (isset($_SESSION['admin_setup']) && $_SESSION['admin_setup'] == true) {
    Redirect::to('?step=conversion');
    exit();
}

if (! isset($_SESSION['site_initialized']) || $_SESSION['site_initialized'] != true) {
    Redirect::to('?step=site_configuration');
    exit();
}

require ROOT_PATH . '/core/includes/password.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $validate = new Validate();
    $validation = $validate->check($_POST, [
        'username' => [
            'required' => true,
            'min' => 3,
            'max' => 20,
        ],
        'email' => [
            'required' => true,
            'min' => 4,
            'max' => 64,
            'email' => true,
        ],
        'password' => [
            'required' => true,
            'min' => 6,
            'max' => 64,
        ],
        'password_again' => [
            'required' => true,
            'matches' => 'password',
        ],
    ]);

    if (! $validation->passed()) {
        foreach ($validation->errors() as $item) {
            if (strpos($item, 'is required') !== false) {
                $error = $language['input_required'];
            } elseif (strpos($item, 'minimum') !== false) {
                $error = $language['input_minimum'];
            } elseif (strpos($item, 'maximum') !== false) {
                $error = $language['input_maximum'];
            } elseif (strpos($item, 'must match') !== false) {
                $error = $language['passwords_must_match'];
            } elseif (strpos($item, 'not a valid email') !== false) {
                $error = $language['email_invalid'];
            }
        }
    } else {
        $user = new User();
        $password = password_hash(Input::get('password'), PASSWORD_BCRYPT, ['cost' => 13]);

        try {
            $queries = new Queries();

            $language = $queries->getWhere('languages', ['is_default', '=', 1]);

            $ip = $user->getIP();
            $user->create([
                'username' => Output::getClean(Input::get('username')),
                'nickname' => Output::getClean(Input::get('username')),
                'password' => $password,
                'pass_method' => 'default',
                'uuid' => 'none',
                'joined' => date('U'),
                'email' => Output::getClean(Input::get('email')),
                'lastip' => $ip,
                'active' => 1,
                'last_online' => date('U'),
                'theme_id' => 1,
                'language_id' => $language[0]->id,
            ]);

            $login = $user->login(Input::get('email'), Input::get('password'), true);
            if ($login) {
                $_SESSION['admin_setup'] = true;
                $user->addGroup(2);

                Redirect::to('?step=conversion');
                exit();
            }

            $error = $language['unable_to_login'];

            $queries->delete('users', ['id', '=', 1]);
        } catch (Exception $e) {
            $error = $language['unable_to_create_account'] . ': ' . $e->getMessage();
        }
    }
}

if (isset($error)) {
    ?>
	<div class="ui error message">
		<?php echo $error; ?>
	</div>
<?php
} ?>

<form action="" method="post" id="form-user">
	<div class="ui segments">
		<div class="ui secondary segment">
			<h4 class="ui header">
				<?php echo $language['creating_admin_account']; ?>
			</h4>
		</div>
		<div class="ui segment">
			<p><?php echo $language['enter_admin_details']; ?></p>
			<div class="ui centered grid">
				<div class="sixteen wide mobile twelve wide tablet ten wide computer column">
					<div class="ui form">
						<?php
                            create_field('text', $language['username'], 'username', 'inputUsername', getenv('NAMELESS_ADMIN_USERNAME') ?: '');
                            create_field('email', $language['email_address'], 'email', 'inputEmail', getenv('NAMELESS_ADMIN_EMAIL') ?: '');
                            create_field('password', $language['password'], 'password', 'inputPassword');
                            create_field('password', $language['confirm_password'], 'password_again', 'inputPasswordAgain');
                        ?>
					</div>
				</div>
			</div>
		</div>
		<div class="ui right aligned secondary segment">
			<button type="submit" class="ui small primary button">
				<?php echo $language['proceed']; ?>
			</button>
		</div>
	</div>
</form>
