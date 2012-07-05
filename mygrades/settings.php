

<?php

$settings->add(new admin_setting_heading(
            'headerconfig',
            get_string('headerconfig', 'block_my_grades'),
            get_string('descconfig', 'block_my_grades')
        ));
 
$settings->add(new admin_setting_configcheckbox(
            'my_grades/Allow_HTML',
            get_string('labelallowhtml', 'block_my_grades'),
            get_string('descallowhtml', 'block_my_grades'),
            '0'
		));
 

