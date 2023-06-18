// Add an event listener to the "Save Changes" button
const saveButton = document.getElementById('saveButton');
saveButton.addEventListener('click', async () => {
  const editableFields = document.querySelectorAll('.editable-field');

  // Loop through each editable field and save the changes
  for (let i = 0; i < editableFields.length; i++) {
    const field = editableFields[i];
    const id = field.dataset.id;
    const updatedField = field.dataset.field;
    const updatedValue = field.textContent;

    // Send a PATCH request to update the field in the database
    await fetch(`/ip/${id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ [updatedField]: updatedValue })
    });
  }

  // Refresh the page after saving changes
  location.reload();
});



// Hide UnHide unused addresses in network info block
document.querySelectorAll('.toggle-arrow').forEach(arrow => {
    arrow.addEventListener('click', () => {
      const container = arrow.parentElement.parentElement;
      const unusedAddresses = container.querySelector('.unused-addresses');
      container.classList.toggle('collapsed');
      unusedAddresses.style.display = container.classList.contains('collapsed') ? 'none' : 'block';
    });
  });
  
  