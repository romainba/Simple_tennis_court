<?php
defined('_JEXEC') or die;

$wa = $this->app->getDocument()->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('mod_court_usage');
$wa->useStyle('mod_court_usage.stylesheet');
$wa->useScript('mod_court_usage.mod_users_table');

$currentYear = (int) date('Y');

?>

<div style="display:flex;align-items:center;gap:8px;">
    <label for="usersTableYear">Année </label>
    <select id="usersTableYear" onchange="usersTable('usersTable', this.value)" style="width:auto;">
<?php for ($y = $currentYear; $y >= 2017; $y--): ?>
        <option value="<?php echo $y; ?>"<?php echo $y == $currentYear ? ' selected' : ''; ?>><?php echo $y; ?></option>
<?php endfor; ?>
    </select>
</div>
<div id="usersTable" style="margin-top:12px;"></div>
