<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A course bought and PAID for. Rows are only created once the payment gateway has confirmed the payment.
 *
 * For the developer who adds the payment gateway – call this from the gateway's "payment succeeded" handler:
 *
 *     CourseBooking::recordPaid($course, $student, ['gateway' => 'stripe', 'payment_reference' => $paymentId]);
 *
 * Calling it twice with the same payment_reference does not create a second booking.
 */
class CourseBooking extends Model
{
    protected $fillable = ['course_plan_id', 'course_title', 'exam', 'student_id', 'student_name', 'student_email', 'student_phone',
        'amount', 'currency', 'gateway', 'payment_reference', 'paid_at'];

    protected $casts = ['paid_at' => 'datetime', 'amount' => 'float'];

    public static function recordPaid(Course $course, Student $student, array $payment = []): self
    {
        $ref = $payment['payment_reference'] ?? null;
        if ($ref && ($existing = self::where('payment_reference', $ref)->first())) {
            return $existing;
        }

        return self::create([
            'course_plan_id' => $course->id, 'course_title' => $course->title, 'exam' => $course->exam,
            'student_id' => $student->id, 'student_name' => $student->name, 'student_email' => $student->email,
            'student_phone' => $payment['phone'] ?? null,
            'amount' => $payment['amount'] ?? $course->price, 'currency' => $payment['currency'] ?? $course->currency,
            'gateway' => $payment['gateway'] ?? null, 'payment_reference' => $ref, 'paid_at' => $payment['paid_at'] ?? now(),
        ]);
    }

    public function amountLabel(): string
    {
        $n = rtrim(rtrim(number_format($this->amount, 2, '.', ','), '0'), '.');

        return strtoupper($this->currency) === 'USD' ? '$' . $n . ' USD' : $n . ' ' . strtoupper($this->currency);
    }
}
