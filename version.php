<?php

/**
 * Project : Technical Project Manager (IEEE like)
 *
 * @package mod-techproject
 * @subpackage framework
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of project
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

defined('MOODLE_INTERNAL') || die;

$module->version  = 2013050900;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2013040500;  // Requires this Moodle version
$module->component = 'mod_techproject';   // Full name of the plugin (used for diagnostics)
$module->cron     = 0;           // Period for cron to check this module (secs)
$module->maturity = MATURITY_RC;
$module->release = '2.5.0 (Build 2013050900)';

