const SalesManager = {
    loadSales: async function(startDate, endDate) {
        try {
            const response = await fetch(`includes/api.php?action=getSales&start_date=${startDate}&end_date=${endDate}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message);
            }

            this.displaySales(data.data);
            this.updateDateRange(startDate, endDate);
        } catch (error) {
            console.error('Error loading sales:', error);
            alert('Error loading sales data: ' + error.message);
        }
    },

    displaySales: function(data) {
        const tbody = document.getElementById('sales-data');
        const totalCell = document.getElementById('total-sales');
        
        tbody.innerHTML = '';
        
        data.sales.forEach(sale => {
            const row = `
                <tr>
                    <td>${sale.item_name}</td>
                    <td>${sale.quantity_sold}</td>
                    <td>₱${parseFloat(sale.price).toFixed(2)}</td>
                    <td>₱${parseFloat(sale.total_sales).toFixed(2)}</td>
                    <td>
                        <button onclick="SalesManager.deleteSale(${sale.id})" class="delete">
                            🗑️ Delete
                        </button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });

        totalCell.textContent = `₱${parseFloat(data.total).toFixed(2)}`;
    },

    updateDateRange: function(startDate, endDate) {
        const dateRange = document.getElementById('date-range');
        dateRange.textContent = `${startDate} to ${endDate}`;
    },

    deleteSale: async function(saleId) {
        if (!confirm('Are you sure you want to delete this sale?')) {
            return;
        }

        try {
            const response = await fetch('includes/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'deleteSale',
                    id: saleId
                })
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message);
            }

            // Reload the sales data
            this.loadSales(
                document.getElementById('start_date').value,
                document.getElementById('end_date').value
            );
        } catch (error) {
            console.error('Error deleting sale:', error);
            alert('Error deleting sale: ' + error.message);
        }
    },

    generateReport: function() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        // Open in a new window to handle PDF display
        window.open(`includes/api.php?action=generatePDF&start_date=${startDate}&end_date=${endDate}`, '_blank');
    },

    printReport: function() {
        const printContents = document.querySelector('.sales-table').innerHTML;
        const originalContents = document.body.innerHTML;
        
        document.body.innerHTML = `
            <div class="print-header">
                <h1>Sales Report</h1>
                <p>Date Range: ${document.getElementById('date-range').textContent}</p>
            </div>
            ${printContents}
        `;
        
        window.print();
        document.body.innerHTML = originalContents;
        
        // Reinitialize the sales manager after restoring content
        this.loadSales(
            document.getElementById('start_date').value,
            document.getElementById('end_date').value
        );
    },

    editReport: function() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        const rows = document.querySelectorAll('#sales-data tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 4) {
                cells[1].contentEditable = true;
                cells[2].contentEditable = true;
                cells[1].classList.add('editable');
                cells[2].classList.add('editable');
            }
        });
        
        // Add save button if it doesn't exist
        if (!document.getElementById('saveChanges')) {
            const saveBtn = document.createElement('button');
            saveBtn.id = 'saveChanges';
            saveBtn.className = 'save-changes';
            saveBtn.textContent = '💾 Save Changes';
            saveBtn.onclick = () => this.saveChanges();
            document.querySelector('.function-buttons').appendChild(saveBtn);
        }
    },

    saveChanges: async function() {
        const updatedSales = [];
        const rows = document.querySelectorAll('#sales-data tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 4) {
                const id = row.querySelector('.delete').getAttribute('onclick').match(/\d+/)[0];
                updatedSales.push({
                    id: id,
                    quantity: parseInt(cells[1].textContent),
                    price: parseFloat(cells[2].textContent.replace('₱', ''))
                });
            }
        });

        try {
            const response = await fetch('includes/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'updateSales',
                    sales: updatedSales
                })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message);
            }

            // Reload the sales data
            this.loadSales(
                document.getElementById('start_date').value,
                document.getElementById('end_date').value
            );

            // Remove save button and editable states
            document.getElementById('saveChanges').remove();
            document.querySelectorAll('.editable').forEach(cell => {
                cell.contentEditable = false;
                cell.classList.remove('editable');
            });

            alert('Changes saved successfully!');
        } catch (error) {
            console.error('Error saving changes:', error);
            alert('Error saving changes: ' + error.message);
        }
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    const today = new Date();
    const defaultStart = new Date(today);
    defaultStart.setDate(today.getDate() - 3);
    
    document.getElementById('start_date').value = defaultStart.toISOString().split('T')[0];
    document.getElementById('end_date').value = today.toISOString().split('T')[0];
    
    SalesManager.loadSales(
        document.getElementById('start_date').value,
        document.getElementById('end_date').value
    );
});
