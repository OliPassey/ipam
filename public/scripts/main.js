// Add an event listener to the table rows for field editing
const editableFields = document.querySelectorAll('.editable-field');
editableFields.forEach(field => {
  field.addEventListener('blur', async () => {
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
  });
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
  
  