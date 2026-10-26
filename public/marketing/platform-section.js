// Native tabs: mouse, touch and keyboard all select the same local sample panels.
const platformTabs = [...document.querySelectorAll('.platform-tabs [role="tab"]')];

function selectPlatformTab(selectedTab, moveFocus = false) {
  platformTabs.forEach(tab => {
    const selected = tab === selectedTab;
    tab.setAttribute('aria-selected', String(selected));
    tab.tabIndex = selected ? 0 : -1;
    document.getElementById(tab.getAttribute('aria-controls')).hidden = !selected;
  });
  if (moveFocus) selectedTab.focus();
}

platformTabs.forEach((tab, index) => {
  tab.addEventListener('click', () => selectPlatformTab(tab));
  tab.addEventListener('keydown', event => {
    let nextIndex;
    if (event.key === 'ArrowRight') nextIndex = (index + 1) % platformTabs.length;
    else if (event.key === 'ArrowLeft') nextIndex = (index - 1 + platformTabs.length) % platformTabs.length;
    else if (event.key === 'Home') nextIndex = 0;
    else if (event.key === 'End') nextIndex = platformTabs.length - 1;
    else return;
    event.preventDefault();
    selectPlatformTab(platformTabs[nextIndex], true);
  });
});
