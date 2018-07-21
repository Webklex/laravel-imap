<?php
/*
* File:     Query.php
* Category: -
* Author:   M. Goldenbaum
* Created:  21.07.18 18:54
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\IMAP\Query;

use Webklex\IMAP\Exceptions\InvalidWhereQueryCriteriaException;

/**
 * Class Query
 *
 * @package Webklex\IMAP\Query
 */
class WhereQuery extends Query {

    /**
     * @var array $available_criteria
     */
    protected $available_criteria = [
        'OR', 'AND',
        'ALL', 'ANSWERED', 'BCC', 'BEFORE', 'BODY', 'CC', 'DELETED', 'FLAGGED', 'FROM', 'KEYWORD',
        'NEW', 'OLD', 'ON', 'RECENT', 'SEEN', 'SINCE', 'SUBJECT', 'TEXT', 'TO',
        'UNANSWERED', 'UNDELETED', 'UNFLAGGED', 'UNKEYWORD', 'UNSEEN'
    ];

    /**
     * @param $criteria
     *
     * @return string
     * @throws InvalidWhereQueryCriteriaException
     */
    protected function validate_criteria($criteria){
        $criteria = strtoupper($criteria);

        if(in_array($criteria, $this->available_criteria) === false) {
            throw new InvalidWhereQueryCriteriaException();
        }

        return $criteria;
    }

    /**
     * @param string|array $criteria
     * @param mixed $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     */
    public function where($criteria, $value = null){
        if(is_array($criteria)){
            foreach($criteria as $arguments){
                if(count($arguments) == 1){
                    $this->where($arguments[0]);
                }elseif(count($arguments) == 2){
                    $this->where($arguments[0], $arguments[1]);
                }
            }
        }else{
            $criteria = $this->validate_criteria($criteria);
            $value = $this->parse_value($value);

            if($value === null || $value === ''){
                $this->query->push([$criteria]);
            }else{
                $this->query->push([$criteria, $value]);
            }
        }

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function orWhere($value){
        return $this->where('OR', $value);
    }

    /**
     * @param mixed $value
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function andWhere($value){
        return $this->where('AND', $value);
    }

    /**
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereAll(){
        return $this->where('ALL');
    }

    /**
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereAnswered(){
        return $this->where('ANSWERED');
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereBcc($value){
        return $this->where('BCC', $value);
    }

    /**
     * @param mixed $value
     *
     * @return WhereQuery
     * @throws \Webklex\IMAP\Exceptions\MessageSearchValidationException
     */
    public function whereBefore($value){
        $date = $this->parse_date($value);
        return $this->where('BEFORE', $date);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereBody($value){
        return $this->where('BODY', $value);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereCc($value){
        return $this->where('CC', $value);
    }

    /**
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereDeleted(){
        return $this->where('DELETED');
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereFlagged($value){
        return $this->where('FLAGGED', $value);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereFrom($value){
        return $this->where('FROM', $value);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereKeyword($value){
        return $this->where('KEYWORD', $value);
    }

    /**
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereNew(){
        return $this->where('NEW');
    }

    /**
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereOld(){
        return $this->where('OLD');
    }

    /**
     * @param mixed $value
     *
     * @return WhereQuery
     * @throws \Webklex\IMAP\Exceptions\MessageSearchValidationException
     */
    public function whereOn($value){
        $date = $this->parse_date($value);
        return $this->where('ON', $date);
    }

    /**
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereRecent(){
        return $this->where('RECENT');
    }

    /**
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereSeen(){
        return $this->where('SEEN');
    }

    /**
     * @param mixed $value
     *
     * @return WhereQuery
     * @throws \Webklex\IMAP\Exceptions\MessageSearchValidationException
     */
    public function whereSince($value){
        $date = $this->parse_date($value);
        return $this->where('SINCE', $date);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereSubject($value){
        return $this->where('SUBJECT', $value);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereText($value){
        return $this->where('TEXT', $value);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereTo($value){
        return $this->where('TO', $value);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUnkeyword($value){
        return $this->where('UNKEYWORD', $value);
    }

    /**
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUnanswered(){
        return $this->where('UNANSWERED');
    }

    /**
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUndeleted(){
        return $this->where('UNDELETED');
    }

    /**
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUnflagged(){
        return $this->where('UNFLAGGED');
    }

    /**
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUnseen(){
        return $this->where('UNSEEN');
    }
}