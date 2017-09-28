<?php
// James Bowler / jamesmbowler@gmail.com

function parse_request($request, $secret)
{
  //first, undo strtr change made in make_request
  $string = strtr($request, '-_', '+/');

  $exp = explode('.', $string);
  
  //decode second part of request
  $payload = base64_decode($exp[1]);
  
  /**
   * for the substr case, let's get length of base64_encoded json, 
   * as when = is appended, as padding, this function returns true
   * 
   * So, if strlen of decoded and encoded is different, return false
   */ 

  $encoded = base64_encode($payload);
  if(strlen($exp[1]) != strlen($encoded)){
    return false;
  }
  
  $decoded = json_decode($payload, true);
  
  /**
   * json_decode will === NULL if json is invalid, so return false
   */ 
  
  return ($decoded === NULL) ? false : $decoded;
  
}

function dates_with_at_least_n_scores($pdo, $n)
{
  $sql = "select date, count(1) as scores
    from scores
    group by date
    having scores >= $n
    order by date desc";
  foreach(  $pdo->query($sql) as $date){
    $dates[]=$date['date'];
  }
  return $dates;
}

function users_with_top_score_on_date($pdo, $date)
{
  $sql = "select *
    from scores
    where date='$date'
    and score = (select max(score)
      from scores
      where date='$date')";

  foreach($pdo->query($sql) as $date){
    $dates[]=$date['user_id'];
  }

  return $dates;

}

function dates_when_user_was_in_top_n($pdo, $user_id, $n)
{
  $dates = [];

  /**
   * There may be a better way to do this ..
   */ 
  $datesql = "select date from scores group by date order by date desc";

  foreach($pdo->query($datesql) as $d){

    $sql = "select *
      from scores s
      where
        ((user_id in (
          select user_id from scores 
          where date='".$d['date']."'
          order by score desc
          limit $n))
        OR
        (score = (
          select MAX(score) from scores 
          where date='".$d['date']."'
         ))
      )
      and user_id=$user_id
      and date='".$d['date']."'";

    $stmt = $pdo->query($sql);

    if(count($stmt->fetchAll()) > 0){
      $dates[]=$d['date'];
    }
  }

  return $dates;
}
