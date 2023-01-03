<?php

/**
 * DokuWiki Plugin tplinc (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <dokuwiki@cosmocode.de>
 */
class admin_plugin_tplinc extends DokuWiki_Admin_Plugin
{

    /** @var helper_plugin_tplinc */
    protected $helper;

    /**
     * admin_plugin_tplinc constructor.
     */
    public function __construct()
    {
        $this->helper = plugin_load('helper', 'tplinc');
    }

    /**
     * @return bool true if only access for superuser, false is for superusers and moderators
     */
    public function forAdminOnly()
    {
        return true;
    }

    /**
     * Should carry out any processing required by the plugin.
     */
    public function handle()
    {
        global $INPUT;

        if ($INPUT->str('action') == 'save' && checkSecurityToken()) {
            if ($this->helper->saveAssignments($INPUT->arr('a'))) {
                msg($this->getLang('saved'), 1);
            }
        }
    }

    /**
     * Render HTML output, e.g. helpful text and a form
     */
    public function html()
    {
        global $ID;
        echo $this->locale_xhtml('intro');

        echo '<form action="' . wl($ID) . '" id="plugin__tplinc" method="POST">';
        echo '<input type="hidden" name="do" value="admin" />';
        echo '<input type="hidden" name="page" value="tplinc" />';
        echo '<input type="hidden" name="sectok" value="' . getSecurityToken() . '" />';

        echo '<table class="inline">';

        // header
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . $this->getLang('pattern') . '</th>';
        echo '<th>' . $this->getLang('page') . '</th>';
        echo '<th>' . $this->getLang('location') . '</th>';
        echo '<th>' . $this->getLang('skipacl') . '</th>';
        echo '<th></th>';
        echo '</tr>';
        echo '</thead>';

        echo '<tbody>';
        // existing assignments
        $assignments = $this->helper->loadAssignments();
        $row = 0;
        foreach ($assignments as $assignment) {
            $this->assignmentRow($assignment, $row);
            $row++;
        }

        // three more rows for new ones
        for ($i = 0; $i < 3; $i++) {
            $this->assignmentRow(array('', '', '', ''), $row);
            $row++;
        }
        echo '<tbody>';

        // save button

        echo '<tfoot>';
        echo '<tr>';
        echo '<td colspan="5"><button type="submit" name="action" value="save">' . $this->getLang('save') . '</button></td>';
        echo '</tr>';
        echo '</tfoot>';

        echo '</table>';
        echo '</form>';

        echo $this->locale_xhtml('help');

    }

    /**
     * One row in the table
     *
     * @param array $assignment
     * @param int $row the row counter
     */
    protected function assignmentRow($assignment, $row)
    {
        list($pattern, $page, $location, $skipacl) = $assignment;
        echo '<tr>';
        echo '<td><input type="text" name="a[x' . $row . '][0]" value="' . hsc($pattern) . '" /></td>';
        echo '<td><input type="text" name="a[x' . $row . '][1]" value="' . hsc($page) . '" /></td>';
        echo '<td>';
        $this->locationBox('a[x' . $row . '][2]', $location);

        echo '<td>';
        $checked = $skipacl ? 'checked="checked"' : '';
        echo '<label>';
        echo '<input type="checkbox" name="a[x' . $row . '][3]" value="1" ' . $checked . '/> ';
        echo $this->getLang('skipacl');
        echo '</label>';
        echo '</td>';

        echo '<td class="drag">' . inlineSVG(__DIR__ . '/drag.svg') . '</td>';
        echo '</tr>';
    }

    /**
     * Create a dropdown for the locations
     *
     * @param string $name the parameter name
     * @param string $loc the current location value
     */
    protected function locationBox($name, $loc)
    {
        $locations = null;
        if ($locations === null) $locations = $this->helper->getLocations();

        if (!isset($locations[$loc])) $loc = '';

        echo '<select name="' . $name . '">';
        foreach ($locations as $location => $label) {
            $selected = ($location == $loc) ? 'selected="selected"' : '';
            echo '<option value="' . hsc($location) . '" ' . $selected . '>';
            echo hsc($label);
            echo '</option>';
        }
        echo '</select>';
    }
}
