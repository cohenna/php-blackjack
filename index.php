<?php
/*
  This file is part of 'Nick Cohen's Blackjack.'
  
  Copyright © 2009-2010 Nick Cohen
  
  'Nick Cohen's Blackjack' is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.
  
  'Nick Cohen's Blackjack' is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
  
  You should have received a copy of the GNU General Public License
  along with 'Nick Cohen's Blackjack'.  If not, see <http://www.gnu.org/licenses/>.
*/
?>
<?php
// system defines
define('SYSPATH', '.');

// system includes
require_once 'blackjack.php';

// blackjack defines
define('MAX_WAGER', 500);
define('MIN_WAGER', 50);
define('WAGER_STEP', 50);

function getImage($card) {
  if($card) {
    $rank = $card->getRank()->getRank();
    if($rank == Rank::JACK) {
      $rank = 'j';
    } elseif($rank == Rank::QUEEN) {
      $rank = 'q';
    } elseif($rank == Rank::KING) {
      $rank = 'k';
    }
    $suit = strtolower(substr($card->getSuit(), 0, 1));
    $image = '/blackjack/images/'.$suit.$rank.'.png';
    return "<img src=\"$image\" />";
  }
}

session_start();

#$deck = new Deck();

$resetgame = false;
$resetwallet = false;
if (isset($_POST['Reset'])) {
  $resetgame = true;
  $resetwallet = true;
}

if ($resetgame) {
  unset($_SESSION['game']);
}
#return;
//if (!isset($_SESSION['game']) || isset($_POST['Deal'])) {
if (isset($_POST['Deal'])) {
  $deal = true;
}

if (!isset($_SESSION['wallet'])) {
  $resetwallet = true;
}

if ($resetwallet) {
  $wallet = new Wallet(1000, 50);
  $_SESSION['wallet'] = $wallet;
} else {
  $wallet = $_SESSION['wallet'];
}

if (isset($_POST['SubmitWager']) && isset($_POST['Wager']) && $_POST['Wager'] <= MAX_WAGER && $_POST['Wager'] >= MIN_WAGER) {
  if (!isset($game) || $game == null || !State::isActive($game->getState())) {
    $wallet->setWager($_POST['Wager']);
  }
}

if ($deal) {
  $game = new BlackjackGame();
  $_SESSION['game'] = $game;
  $game->start();
} else {
  $game = $_SESSION['game'];
}

$dealEnabled = 1;
$hitEnabled = 0;
$standEnabled = 0;
$doubleEnabled = 0;
$insuranceEnabled = 0;
$wagerEnabled = 1;
$gameOn = false;

if (isset($game) && $game != null) {
  $gameOn = true;

  # check for user actions
  if (isset($_POST['Hit'])) {
    $game->hitUser();
  } elseif (isset($_POST['Stand'])) {
    $game->userStands();
  } elseif (isset($_POST['Double'])) {
    $game->userDoubleDown();
  } elseif (isset($_POST['BuyInsurance'])) {
    $game->buyInsurance(true);
  } elseif (isset($_POST['DeclineInsurance'])) {
    $game->buyInsurance(false);
  }

  $upcard = $game->getDealerHand()->getUpCard();
  $dealerScore = $game->getDealerHand()->getScore();
  $dealerHand = $game->getDealerHand();
  $userScore = $game->getUserHand()->getScore();
  $userHand = $game->getUserHand();
  $state = $game->getState();

  if (State::isActive($state)) {
    $dealEnabled = 0;
    $hitEnabled = 1;
    $standEnabled = 1;
    $wagerEnabled = 0;
    if ($state == State::PLAYING) {
      $doubleEnabled = 1;
    }
  } else {
    $wallet->reconcile($game);
    unset($_SESSION['game']);
    $dealEnabled = 1;
    $hitEnabled = 0;
    $standEnabled = 0;
    $doubleEnabled = 0;
  }
  if ($game->getState() == State::OFFER_INSURANCE) {
    $dealEnabled = 0;
    $hitEnabled = 0;
    $standEnabled = 0;
    $doubleEnabled = 0;
    $insuranceEnabled = 1;
    $wagerEnabled = 0;
  }
}

// include view
$mobileview = FALSE; # could support additional views
if($mobileview) {
  require_once 'browserview.php';
}
else {
  require_once 'browserview.php';
}

?>
