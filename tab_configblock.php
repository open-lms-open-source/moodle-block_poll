<?php
// Paul Holden 24th July, 2007
// poll block; block configuration tab

$availpolls = get_records('block_poll', 'courseid', $COURSE->id);
if($availpolls !== false)
{
    foreach ($availpolls as $poll) {
        $menu[$poll->id] = $poll->name;
    }

    $table = new Object();
    $table->head = array(get_string('config_param', 'block_poll'), get_string('config_value', 'block_poll'));
    $table->tablealign = 'left';
    $table->width = '*';

    $table->data[] = array(get_string('editpollname', 'block_poll'), choose_from_menu($menu, 'pollid', (isset($this->config->pollid) ? $this->config->pollid : ''), 'choose', '', 0, true));
    $table->data[] = array(get_string('editblocktitle', 'block_poll'), '<input type="text" name="customtitle" value="' . (isset($this->config->pollid) ? $this->config->customtitle : '') . '" />');
    $table->data[] = array(get_string('editmaxbarwidth', 'block_poll'), '<input type="text" name="maxwidth" value="' . (isset($this->config->maxwidth) ? $this->config->maxwidth : '') . '" />');
    $table->data[] = array('&nbsp;', '<input type="submit" value="' . get_string('savechanges') . '" />');

    print_table($table);

} else {
    echo 'No polls are currently available, select the create/edit poll tab above to create one.';
}

?>
