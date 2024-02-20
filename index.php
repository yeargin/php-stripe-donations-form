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

$success = null;
$submitted = false;
if ($_POST) {
    \Stripe\Stripe::setApiKey(STRIPE_API_KEY);
    $submitted = true;
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
        $charge = array(
          'amount' => $amount * 100,
          'description' => sprintf('%s - $%s on %s', $_POST['name'], number_format($_POST['amount'], 2), date('m/d/Y')),
          'currency' => "usd",
          'card' => $_POST['stripeToken'],
          'metadata' => array(
            'name' => $_POST['name'],
            'address' => $_POST['address'],
            'city' => $_POST['city'],
            'state' => $_POST['state'],
            'zip' => $_POST['zip'],
            'employer' => $_POST['employer'],
            'occupation' => $_POST['occupation']
          ),
        );
        if ($_POST['email']) {
            $charge['receipt_email'] = $_POST['email'];
        }
        \Stripe\Charge::create($charge);
        $success = true;
    } catch (Exception $e) {
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
<meta name="description" content="Securely contribute to our campaign.">
<script src="https:///js.stripe.com/v2/" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

<script>
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
    console.log(response);
    if (response.error) {
      $('.error')
        .removeClass('collapse')
        .find('.alert')
        .text(response.error.message);
    } else {
      // token contains id, last4, and card type
      var token = response['id'];
      // insert the token into the form so it gets submitted to the server
      $form.find('input[type=text]').empty();
      $form.append("<input type='hidden' name='stripeToken' value='" + token + "'>");
      $form.get(0).submit();
    }
  }
});
</script>

<link rel="stylesheet" href="assets/css/donation-form.css">

</head>
<body>

<div class="container">
  <div class="row mb-3">
    <div class="col-12 col-md-6 offset-0 offset-md-3">
      <div class="logo text-center my-3">
        <img src="./assets/img/logo.png" class="img-fluid" alt="Campaign Logo" />
      </div>
      <?php if (!$submitted || (!$success && !$error)): ?>
      <form class="require-validation" data-cc-on-file="false" data-stripe-publishable-key="<?php echo STRIPE_PUBLISHABLE_KEY; ?>" id="payment-form" method="post">
        <div class="row g-3">
          <div class="col-12 form-group required">
            <label class="form-label">Full Name</label>
            <input class="form-control card-name" size="4" name="name" type="text" autocomplete="cc-name" required />
          </div>

          <div class="col-12 form-group required">
            <label class="form-label">Address</label>
            <input class="form-control" size="4" name="address" type="text" autocomplete="street-address" required />
          </div>

          <div class="col-4 form-group required">
            <label class="form-label">City</label>
            <input class="form-control" size="4" name="city" type="text" autocomplete="address-level2" required />
          </div>
          <div class="col-4 form-group required">
            <label class="form-label">State</label>
            <input class="form-control" size="4" name="state" type="text" autocomplete="address-level1" required />
          </div>
          <div class="col-4 form-group required">
            <label class="form-label">Zip Code</label>
            <input class="form-control" size="4" name="zip" type="text" autocomplete="postal-code" required />
          </div>

          <div class="col-12">
            <hr>
          </div>

          <div class="col-12 form-group required">
            <label class="form-label">Card Number</label>
            <input autocomplete="off" class="form-control card-number" size="20" type="text" autocomplete="cc-number" required />
          </div>

          <div class="col-4 form-group cvc required">
            <label class="form-label">CVC</label>
            <input autocomplete="off" class="form-control card-cvc" placeholder="ex. 311" size="4" type="text" autocomplete="cc-csc" required />
          </div>
          <div class="col-4 form-group expiration required">
            <label class="form-label">Expiration</label>
            <input class="form-control card-expiry-month" placeholder="MM" size="2" type="text" autocomplete="cc-exp-month" required />
          </div>
          <div class="col-4 form-group expiration required">
            <label class="form-label">&nbsp;</label>
            <input class="form-control card-expiry-year" placeholder="YYYY" size="4" type="text" autocomplete="cc-exp-year" required />
          </div>
          <div class="col-12">
            <div class="card mb-3">
              <div class="card-header">
                <span class="card-title">Choose an Amount</span>
              </div>
              <div class="card-body">
                <div id="auto_select">
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="auto_select" id="auto_1" value="25" />
                    <label class="form-check-label" for="auto_1">$25</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="auto_select" id="auto_2" value="50" />
                    <label class="form-check-label" for="auto_2">$50</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="auto_select" id="auto_3" value="100" />
                    <label class="form-check-label" for="auto_3">$100</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="auto_select" id="auto_4" value="250" />
                    <label class="form-check-label" for="auto_4">$250</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="auto_select" id="other" value="other" />
                    <label class="form-check-label" for="other">Other</label>
                  </div>
                </div>
                <div id="other_amount" class="collapse">
                  <div class="input-group input-group-lg">
                    <span class="input-group-text">$</span>
                    <input type="text" class="form-control" style="text-align:right" name="amount" id="amount" value="25" />
                    <span class="input-group-text">.00</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <hr>

        <div class="row mb-3">
          <div class="col-6">
            <div class="form-group required">
              <label class="form-label">Employer</label>
              <input class="form-control" size="4" name="employer" type="text" autocomplete="organization" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Occupation</label>
              <input class="form-control" size="4" name="occupation" autocomplete="organization-title" type="text">
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-12">
            <div class="form-group required">
              <label class="form-label">Email (for receipt)</label>
              <input class="form-control" size="4" name="email" autocomplete="email" type="text">
            </div>
          </div>
        </div>

        <div class="error col-12 error form-group collapse">
          <div class="alert alert-danger">
            Please correct the errors and try again.
          </div>
        </div>

        <div class="col-12 form-group mb-3">
          <button class="btn btn-primary btn-lg submit-button" style="width: 100%;" type="submit">Donate</button>
        </div>

        <?php elseif ($success): ?>
        <div class="card border-success mb-3">
          <div class="card-header text-bg-success h4">Thank you for your support!</div>
          <div class="card-body">
            <p>We're processing your contribution to our campaign. Thank you for your support!</p>
            <p><a href="/" class="btn btn-primary">Back to Website</a></p>
          </div>
        </div>
        <?php else: ?>
        <div class="card border-danger mb-3">
          <div class="card-header text-bg-danger h4">We could not process your contribution.</div>
          <div class="card-body">
            <p class="alert alert-danger"><strong>Error:</strong> <?php echo $error; ?></p>
            <p><a href="/donate" class="btn btn-primary">Try Again</a></p>
          </div>
        </div>
        <?php endif; ?>
        <footer>
          <p class="small">Contributions are not tax deductible. Contributions may only be received from U.S. residents. The person making this contribution agrees that they are at least 18 years of age and that this contribution is made from their own funds and not those of another.</p>
          <hr>
          <p class="footer-credit text-center">
            Paid for by Friends of Our Campaign<br>
            Jane Doe, Treasurer.
          </p>
        </footer>
      </form>
    </div>
  </div>
</div>

</body>
</html>
