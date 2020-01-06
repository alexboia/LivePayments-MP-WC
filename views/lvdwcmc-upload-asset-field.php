<?php
    defined('LVD_WCMC_LOADED') or die;
?>

<tr valign="top">
    <th scope="row" class="titledesc">
        <label>
            <?php echo $fieldInfo['title']; ?>
            <?php if (!empty($fieldInfo['description'])): ?>
                <?php echo $this->get_tooltip_html($fieldInfo); ?>
            <?php endif; ?>
        </label>
    </th>
    <td class="forminp" id="<?php echo $fieldId ?>_file_selector">
        <a href="javascript:void(0)">Alege un fisier de pe disc.</a>
    </td>
</tr>