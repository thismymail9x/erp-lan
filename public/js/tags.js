function openEditTag(tag) {
    document.getElementById('editTagForm').action = baseUrl + 'tags/update/' + tag.id;
    document.getElementById('edit_name').value = tag.name;
    document.getElementById('edit_color').value = tag.color;

    document.getElementById('edit_type').value = tag.type === 'global' ? 'global' : 'private';
    $('#edit_type').trigger('change');

    document.getElementById('edit_module_scope').value = tag.module_scope;
    $('#edit_module_scope').trigger('change');

    document.getElementById('editTagModal').style.display = 'flex';
}
