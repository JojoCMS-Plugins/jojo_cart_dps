<form name="paymentform" method="post" action="{$SECUREURL}/cart/process/{$token}/">
<div class="box contact-form">
    <h2>Pay by credit card</h2>
    <input type="hidden" name="token" id="token" value="{$token}" />
    <input type="hidden" name="paymentmethod" value="dps" />{* this line probably not needed *}
    <input type="hidden" name="handler" value="dps" />
    <label for="cardType">Card Type:</label>
    <select name="cardType" id="cardType">
      <option value="">Select card type</option>
      {section name=c loop=$cardtypes}
      <option value="{$cardtypes[c]}"{if $fields.cardType==$cardtypes[c]} selected="selected"{/if}>{$cardtypes[c]|ucfirst}</option>
      {/section}
    </select><br />
    <label for="cardNumber">Card Number:</label>
    <input type="text" size="30" name="cardNumber" id="cardNumber" value="{$fields.cardNumber}"  autocomplete="off" /><br />
    
    <label for="cardExpiryMonth">Expiry Date:</label>
    <div class="form-field">
    <input type="text" size="2" name="cardExpiryMonth" id="cardExpiryMonth" value="{$fields.cardExpiryMonth}" autocomplete="off" /> / <input type="text" size="2" name="cardExpiryYear" id="cardExpiryYear" value="{$fields.cardExpiryYear}" /> (mm/yy)
    </div><br />
    
    <label for="cardName">Name on card</label>
    <input type="text" size="30" name="cardName" id="cardName" value="{$fields.cardName}" autocomplete="off" /><br />
    
  </div>

<div style="text-align: center;"><input type="submit" name="pay" id="pay" value="Pay by Credit card" onclick="if (true){ldelim}$('#pay').attr('disabled',true);paymentform.submit();{rdelim}else{ldelim}return false;{rdelim}" /></div>

</form>
