// This compares published prices; trial signup and subscription handling stay separate.
document.querySelectorAll('[data-pricing-plan]').forEach(plan => {
  const summary = plan.querySelector('.pricing-selection');
  const options = [...plan.querySelectorAll('input[name="pricing-billing"]')];
  const update = () => {
    const selected = options.find(input => input.checked);
    if (!selected) return;
    plan.dataset.billing = selected.value;
    summary.textContent = selected.value === 'annual'
      ? 'Annual billing: $720 per user, billed once a year ($60/month). Save $180 per user each year.'
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
