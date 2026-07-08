import './bootstrap';
import Alpine from 'alpinejs';
import { registerQuickAccessModal } from './quick-access-modal';

window.Alpine = Alpine;
registerQuickAccessModal();
Alpine.start();
