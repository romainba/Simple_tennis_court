<style>input::-webkit-calendar-picker-indicator {display:none;}</style>
<datalist id="userlist"></datalist>
<joomla-modal 
    id="okModal" 
    class="modal fade" 
    tabindex="-1" 
    aria-hidden="true">
  <div slot="modal-body" id="modalMsgContent"></div>
  <div slot="modal-footer">
    <button type="button" id="modalOkBtn" class="btn btn-primary">OK</button>
  </div>
</joomla-modal>
<div id="sel-player" style="display: none"></div>
<div id="cal-header"></div>
<div id="calendar"></div>

<div class="custom-popup-overlay" id="customPopupOverlay">
    <div class="custom-popup-content">
        <p id="popup-content"></p>
        <button id="closePopupBtn">OK</button>
    </div>
</div>
