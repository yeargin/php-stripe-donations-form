<?php

if (file_exists('config/app-config.php')) {
  require_once 'config/app-config.php';
} else {
  print 'Missing config/app-config.php';
  exit;
}

// Force HTTPS connections
if (STRIPE_REQUIRE_HTTPS === true && $_SERVER['HTTPS'] != 'on') {
  header("HTTP/1.1 301 Moved Permanently");
  header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
  exit();
}

// Composer
require_once 'vendor/autoload.php';

if ($_POST) {
  Stripe::setApiKey(STRIPE_API_KEY);
  $error = '';
  $success = '';
  try {
    $amount = (int) $_POST['amount'];
    if (!isset($_POST['stripeToken'])) {
      throw new Exception("An error occurred processing your donation. Please try again.");
    }
    if ($amount < 1 || $amount > 2600) {
      throw new Exception("Online donations must be greater than $1 and less than or equal to $2,600.");
    }
    if (!$_POST['name'] || !$_POST['address'] || !$_POST['employer']) {
      throw new Exception("We must collect your name, address and employer to comply with the law.");
    }
    Stripe_Charge::create(
      array(
        "amount" => $amount * 100,
        "description" => sprintf('%s - $%s on %s', $_POST['name'], number_format($_POST['amount'],2), date('m/d/Y')),
        "currency" => "usd",
        "card" => $_POST['stripeToken'],
        "metadata" => array(
          "name" => $_POST['name'],
          "address" => $_POST['address'],
          "city" => $_POST['city'],
          "state" => $_POST['state'],
          "zip" => $_POST['zip'],
          "employer" => $_POST['employer'],
          "occupation" => $_POST['occupation']
        ),
        "receipt_email" => $_POST['email']
      )
    );
    $success = true;
  }
  catch (Exception $e) {
    $error = $e->getMessage();
  }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Support Our Campaign</title>
<meta name="description" content="Securely contribute to our campaign." />
<script type="text/javascript" src="//js.stripe.com/v2/"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript">
$(function() {
  $('form.require-validation').bind('submit', function(e) {
    var $form         = $(e.target).closest('form'),
        inputSelector = ['input[type=email]', 'input[type=password]',
                         'input[type=text]', 'input[type=file]',
                         'textarea'].join(', '),
        $inputs       = $form.find('.required').find(inputSelector),
        $errorMessage = $form.find('div.error'),
        valid         = true;

    $errorMessage.addClass('hide');
    $('.has-error').removeClass('has-error');
    $inputs.each(function(i, el) {
      var $input = $(el);
      if ($input.val() === '') {
        $input.parent().addClass('has-error');
        $errorMessage.removeClass('hide');
        e.preventDefault(); // cancel on first error
      }
    });
  });
});

$(function() {

  // Ensure default is set
  $('input#auto_1').prop('checked', true);

  $('input[name="auto_select"]').change(function() {
    var amount;
    amount = $(this).val();
    if (amount == 'other') {
      showOtherAmount();
    } else {
      $('#amount').val(parseInt($(this).val(), 10));
    }
  });

  function showOtherAmount() {
    $('#auto_select').addClass('collapse');
    $('#other_amount').removeClass('collapse');
  }

  var $form = $("#payment-form");

  $form.on('submit', function(e) {
    if (!$form.data('cc-on-file')) {
      e.preventDefault();
      Stripe.setPublishableKey($form.data('stripe-publishable-key'));
      Stripe.createToken({
        name: $('.card-name').val(),
        number: $('.card-number').val(),
        cvc: $('.card-cvc').val(),
        exp_month: $('.card-expiry-month').val(),
        exp_year: $('.card-expiry-year').val()
      }, stripeResponseHandler);
    }
  });

  function stripeResponseHandler(status, response) {
    if (response.error) {
      $('.error')
        .removeClass('hide')
        .find('.alert')
        .text(response.error.message);
    } else {
      // token contains id, last4, and card type
      var token = response['id'];
      // insert the token into the form so it gets submitted to the server
      $form.find('input[type=text]').empty();
      $form.append("<input type='hidden' name='stripeToken' value='" + token + "'/>");
      $form.get(0).submit();
    }
  }
});
</script>

<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-000000-00', 'auto');
ga('send', 'pageview');
<?php if ($success): ?>
ga('send', 'event', 'donate', 'success', '<?php echo htmlentities(addslashes($_POST['email'])); ?>', <?php echo $amount; ?>);
<?php endif; ?>
</script>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" />

<link rel="stylesheet" href="assets/css/donation-form.css" />

</head>
<body>

<div class="container">
    <div class='row'>
        <div class='col-lg-offset-3 col-lg-6'>
          <h1 class="logo">
            <span class="sr-only">Campaign Logo</span>
          </h1>
          <?php if (!$success && !$error): ?>
          <form class="require-validation" data-cc-on-file="false" data-stripe-publishable-key="<?php echo STRIPE_PUBLISHABLE_KEY; ?>" id="payment-form" method="post">
            <div class="row">
              <div class='col-xs-12 form-group required'>
                <label class="control-label">Full Name</label>
                <input class='form-control card-name' size="4" name="name" type="text" required>
              </div>
            </div>
            <div class="row">
              <div class="col-xs-12 form-group required">
                <label class="control-label">Addresss</label>
                <input class='form-control' size="4" name="address" type="text" required>
              </div>
            </div>
            <div class="row">
              <div class="col-xs-4 form-group required">
                <label class="control-label">City</label>
                <input class='form-control' size="4" name="city" type="text" required>
              </div>
              <div class="col-xs-4 form-group required">
                <label class="control-label">State</label>
                <input class='form-control' size="4" name="state" type="text" required>
              </div>
              <div class="col-xs-4 form-group required">
                <label class="control-label">Zip Code</label>
                <input class='form-control' size="4" name="zip" type="text" required>
              </div>
            </div>
            <div class="row">
              <div class="col-xs-12">
                <hr />
              </div>
            </div>
            <div class="row">
              <div class='col-xs-12 form-group card required'>
                <label class="control-label">Card Number</label>
                <input autocomplete="off" class="form-control card-number" size="20" type="text" required>
              </div>
            </div>
            <div class="row">
              <div class='col-xs-4 form-group cvc required'>
                <label class="control-label">CVC</label>
                <input autocomplete="off" class="form-control card-cvc" placeholder="ex. 311" size="4" type="text" required>
              </div>
              <div class='col-xs-4 form-group expiration required'>
                <label class="control-label">Expiration</label>
                <input class='form-control card-expiry-month' placeholder='MM' size="2" type="text" required>
              </div>
              <div class='col-xs-4 form-group expiration required'>
                <label class="control-label">&nbsp;</label>
                <input class='form-control card-expiry-year' placeholder='YYYY' size="4" type="text" required>
              </div>
            </div>
            <div class="row">
              <div class='col-xs-12'>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <span class="panel-title">Choose an Amount</span>
                    </div>
                    <div class="panel-body">
                      <div id="auto_select">
                          <label class="radio-inline">
                            <input type="radio" name="auto_select" id="auto_1" value="25" checked="checked"> $25
                          </label>
                          <label class="radio-inline">
                            <input type="radio" name="auto_select" id="auto_2" value="50"> $50
                          </label>
                          <label class="radio-inline">
                            <input type="radio" name="auto_select" id="auto_3" value="100"> $100
                          </label>
                          <!-- <br /> -->
                          <label class="radio-inline">
                            <input type="radio" name="auto_select" id="auto_4" value="250"> $250
                          </label>
                          <label class="radio-inline">
                            <input type="radio" name="auto_select" id="other" value="other"> Other
                          </label>
                      </div>
                      <div id="other_amount" class="collapse">
                          <div class="input-group input-group-lg">
                            <span class="input-group-addon">$</span>
                            <input type="text" class="form-control" style="text-align:right" name="amount" id="amount" value="25">
                            <span class="input-group-addon">.00</span>
                          </div>
                      </div>
                    </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-xs-6 form-group required">
                <label class="control-label">Employer</label>
                <input class='form-control' size="4" name="employer" type="text" required>
              </div>
              <div class='col-xs-6 form-group'>
                <label class="control-label">Occupation</label>
                <input class='form-control' size="4" name="occupation" type="text">
              </div>
            </div>
            <div class="row">
              <div class="col-xs-12 form-group required">
                <label class="control-label">Email (for receipt)</label>
                <input class='form-control' size="4" name="email" type="text">
              </div>
            </div>
            <div class="row">
              <div class="col-xs-12 form-group">
                <button class="btn btn-primary btn-lg submit-button" style="width: 100%;" type="submit">Donate</button>
              </div>
            </div>
            <div class="row">
              <div class="col-xs-12 error form-group hide">
                <div class="alert-danger alert">
                  Please correct the errors and try again.
                </div>
              </div>
            </div>
          </form>
          <?php elseif ($success): ?>
          <div class="alert alert-success">
            <h2>Thank you for your support!</h2>
            <p>We're processing your contribution to our campaign. Thank you for your support!</p>
          </div>
          <?php else: ?>
          <div class="alert alert-danger">
            <h2>We could not process your contribution.</h2>
            <p><?php echo $error; ?></p>
            <p><a href="/donate" class="btn btn-default">Try Again</a></p>
          </div>
          <?php endif; ?>
          <div class="footer">
            <p class="small">Contributions are not tax deductible. Contributions may only be received from U.S. residents. The person making this contribution agrees that they are at least 18 years of age and that this contribution is made from their own funds and not those of another.</p>
            <p class="footer-credit">
              Paid for by Friends of Our Campaign<br />
              Jane Doe, Treasurer.
            </p>
          </div>
        </div>
    </div>
</div>

<!-- Latest compiled and minified JavaScript -->
<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>

</body>
</html>
