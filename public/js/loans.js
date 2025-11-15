// RCard Loans JavaScript Module

let loansData = [];
let cardsData = [];
let policiesData = {};
let currentRepayLoan = null;

/**
 * Initialize loans module
 */
async function initLoans() {
    await loadLoansData();
    renderLoans();
}

/**
 * Load loans data from API
 */
async function loadLoansData() {
    const result = await apiCall('loans_bootstrap');
    
    if (result.status === 'success') {
        loansData = result.data.loans || [];
        cardsData = result.data.cards || [];
        policiesData = result.data.policies || {};
        
        // Populate card selector
        populateCardSelector();
    } else {
        showToast('Failed to load loans data', 'error');
    }
}

/**
 * Populate card selector in new loan modal
 */
function populateCardSelector() {
    const selector = document.getElementById('loanCardId');
    if (!selector) return;
    
    selector.innerHTML = '<option value="">Choose a card...</option>';
    
    cardsData.forEach(card => {
        const option = document.createElement('option');
        option.value = card.id;
        option.textContent = `${card.card_identifier} (${card.type})`;
        selector.appendChild(option);
    });
}

/**
 * Render loans list
 */
function renderLoans() {
    const container = document.getElementById('loans-wrapper');
    if (!container) return;
    
    if (loansData.length === 0) {
        container.innerHTML = `
            <div class="glass rounded-xl p-12 text-center">
                <div class="text-6xl mb-4">💰</div>
                <h3 class="text-2xl font-bold text-white mb-2">No Active Loans</h3>
                <p class="text-gray-400 mb-6">Create your first loan to get started</p>
                <button onclick="showNewLoanModal()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg">
                    Create Loan
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div class="space-y-6">
            ${loansData.map(loan => renderLoanCard(loan)).join('')}
        </div>
    `;
}

/**
 * Render individual loan card
 */
function renderLoanCard(loan) {
    const principal = parseFloat(loan.principal);
    const interest = parseFloat(loan.interest_accrued || 0);
    const total = parseFloat(loan.total_due || principal + interest);
    const daysRemaining = calculateDaysRemaining(loan.due_date);
    
    const statusColor = loan.status === 'active' ? 'green' : 'gray';
    const urgencyClass = daysRemaining <= 3 ? 'border-red-500' : 'border-purple-500/50';
    
    return `
        <div class="glass rounded-xl p-6 ${urgencyClass} border-2">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="text-gray-400 text-sm">Loan #${loan.id.substring(5, 13)}</span>
                        <span class="px-2 py-1 bg-${statusColor}-500/20 text-${statusColor}-400 rounded text-xs font-bold uppercase">
                            ${loan.status}
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-white">${formatCurrency(principal)}</p>
                    <p class="text-gray-400 text-sm mt-1">
                        Interest Rate: ${loan.interest_rate_monthly}% monthly
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-400 mb-1">Interest Accrued</p>
                    <p class="text-2xl font-bold text-yellow-400">${formatCurrency(interest)}</p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-gray-400 text-sm">Created</p>
                    <p class="text-white font-semibold">${formatDate(loan.created_at)}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-sm">Due Date</p>
                    <p class="text-white font-semibold">${formatDate(loan.due_date)}</p>
                </div>
            </div>
            
            ${daysRemaining >= 0 ? `
                <div class="mb-4 p-3 bg-blue-500/20 border border-blue-500 rounded">
                    <p class="text-blue-300 text-sm">
                        <strong>${daysRemaining}</strong> day${daysRemaining !== 1 ? 's' : ''} remaining
                    </p>
                </div>
            ` : `
                <div class="mb-4 p-3 bg-red-500/20 border border-red-500 rounded">
                    <p class="text-red-300 text-sm">
                        <strong>Overdue</strong> by ${Math.abs(daysRemaining)} day${Math.abs(daysRemaining) !== 1 ? 's' : ''}
                    </p>
                </div>
            `}
            
            <div class="border-t border-gray-700 pt-4 mb-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Total Due</span>
                    <span class="text-2xl font-bold text-green-400">${formatCurrency(total)}</span>
                </div>
            </div>
            
            ${loan.status === 'active' ? `
                <div class="flex space-x-3">
                    <button onclick="initiateRepayment('${loan.id}', ${total})" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded-lg transition">
                        Repay Loan
                    </button>
                    <button onclick="initiateRepayment('${loan.id}', ${total}, true)" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">
                        Pay Full
                    </button>
                </div>
            ` : ''}
        </div>
    `;
}

/**
 * Calculate days remaining until due date
 */
function calculateDaysRemaining(dueDate) {
    const due = new Date(dueDate);
    const today = new Date();
    const diff = Math.ceil((due - today) / (1000 * 60 * 60 * 24));
    return diff;
}

/**
 * Show new loan modal
 */
function showNewLoanModal() {
    document.getElementById('newLoanModal').classList.remove('hidden');
    document.getElementById('newLoanModal').classList.add('flex');
}

/**
 * Hide new loan modal
 */
function hideNewLoanModal() {
    document.getElementById('newLoanModal').classList.add('hidden');
    document.getElementById('newLoanModal').classList.remove('flex');
    document.getElementById('loanPreview').classList.add('hidden');
    
    // Reset form
    document.getElementById('loanCardId').value = '';
    document.getElementById('loanPrincipal').value = '';
    document.getElementById('loanDays').value = '30';
}

/**
 * Update loan preview
 */
async function updateLoanPreview() {
    const cardId = document.getElementById('loanCardId').value;
    const principal = parseFloat(document.getElementById('loanPrincipal').value);
    const days = parseInt(document.getElementById('loanDays').value);
    
    if (!cardId || !principal || !days || principal <= 0 || days <= 0) {
        document.getElementById('loanPreview').classList.add('hidden');
        return;
    }
    
    // Get policy for card
    const policy = policiesData[cardId];
    if (policy) {
        document.getElementById('minDays').textContent = policy.min_interest_days || 5;
    }
    
    // Call preview API
    const result = await apiCall('loans_preview', {
        card_id: cardId,
        principal: principal,
        days: days
    });
    
    if (result.status === 'success') {
        const preview = result.data.preview;
        
        // Update preview display
        document.getElementById('previewPrincipal').textContent = formatCurrency(preview.principal);
        document.getElementById('previewRate').textContent = preview.interest_rate_monthly + '%';
        document.getElementById('previewDays').textContent = preview.effective_days;
        document.getElementById('previewInterest').textContent = formatCurrency(preview.interest_amount);
        document.getElementById('previewTotal').textContent = formatCurrency(preview.total_due);
        
        // Show/hide yearly limit warning
        const warning = document.getElementById('yearlyLimitWarning');
        const createBtn = document.getElementById('createLoanBtn');
        
        if (!preview.yearly_limit.can_borrow) {
            warning.classList.remove('hidden');
            warning.innerHTML = `<strong>Warning:</strong> This loan exceeds your yearly limit! You have R$ ${preview.yearly_limit.remaining.toFixed(2)} remaining.`;
            createBtn.disabled = true;
        } else {
            warning.classList.add('hidden');
            createBtn.disabled = false;
        }
        
        document.getElementById('loanPreview').classList.remove('hidden');
    }
}

/**
 * Create new loan
 */
async function createLoan() {
    const cardId = document.getElementById('loanCardId').value;
    const principal = parseFloat(document.getElementById('loanPrincipal').value);
    const days = parseInt(document.getElementById('loanDays').value);
    
    if (!cardId || !principal || !days) {
        showToast('Please fill all fields', 'error');
        return;
    }
    
    const result = await apiPost('loans_create', {
        card_id: cardId,
        principal: principal,
        days: days
    });
    
    if (result.status === 'success') {
        showToast('Loan created successfully!', 'success');
        hideNewLoanModal();
        await loadLoansData();
        renderLoans();
    } else {
        showToast(result.error || 'Failed to create loan', 'error');
    }
}

/**
 * Initiate loan repayment
 */
function initiateRepayment(loanId, totalDue, payFull = false) {
    currentRepayLoan = loanId;
    document.getElementById('repayTotalDue').textContent = formatCurrency(totalDue);
    
    if (payFull) {
        document.getElementById('repayAmount').value = totalDue.toFixed(2);
    }
    
    document.getElementById('repayLoanModal').classList.remove('hidden');
    document.getElementById('repayLoanModal').classList.add('flex');
}

/**
 * Hide repay loan modal
 */
function hideRepayLoanModal() {
    document.getElementById('repayLoanModal').classList.add('hidden');
    document.getElementById('repayLoanModal').classList.remove('flex');
    document.getElementById('repayAmount').value = '';
    currentRepayLoan = null;
}

/**
 * Repay loan
 */
async function repayLoan() {
    const amount = parseFloat(document.getElementById('repayAmount').value);
    
    if (!amount || amount <= 0) {
        showToast('Please enter a valid amount', 'error');
        return;
    }
    
    if (!currentRepayLoan) {
        showToast('No loan selected', 'error');
        return;
    }
    
    const result = await apiPost('loans_repay', {
        loan_id: currentRepayLoan,
        amount: amount
    });
    
    if (result.status === 'success') {
        showToast('Repayment successful!', 'success');
        hideRepayLoanModal();
        await loadLoansData();
        renderLoans();
        
        // Reload page to update balance
        setTimeout(() => window.location.reload(), 1000);
    } else {
        showToast(result.error || 'Repayment failed', 'error');
    }
}

// Initialize on page load
if (document.getElementById('loans-wrapper')) {
    initLoans();
}
