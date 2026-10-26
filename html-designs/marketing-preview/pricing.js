// This compares published prices; trial signup and subscription handling stay separate.
document.querySelectorAll('[data-pricing-plan]').forEach(plan => {
  const summary = plan.querySelector('.pricing-selection');
  const options = [...plan.querySelectorAll('input[name="pricing-billing"]')];
  const update = () => {
    const selected = options.find(input => input.checked);
    if (!selected) return;
    plan.dataset.billing = selected.value;
    summary.textContent = selected.value === 'annual'
      ? 'Annual billing: $600 per user, billed once a year ($50/month). Save $300 per user each year.'
      : 'Monthly billing: $75 per user, billed each month.';
  };
  options.forEach(input => {
    input.addEventListener('change', () => {
      if (!input.checked) return;
      update();
    });
  });
  update();
});
