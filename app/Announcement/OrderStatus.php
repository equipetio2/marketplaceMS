<?php
    /**
     * Created by PhpStorm.
     * User: anthonyrodrigues
     * Date: 8/30/18
     * Time: 10:57 AM
     */

    namespace App\Announcement;


    class OrderStatus
    {
        /**
         * @var string
         * @uses Status inicial de uma ordem; ainda sem ter sido paga.
         */
        const CONFIRMED = 'confirmed';

        /**
         * @var string
         * @uses O pagamento da ordem deve ter sido confirmado para exibir as informações do usuário.
         */
        const PAYMENT_REQUIRED = 'payment_required';

        /**
         * @var string
         * @uses Há um pagamento relacionado à ordem, mais ainda não foi creditado.
         */
        const PAYMENT_IN_PROCESS = 'payment_in_process';

        /**
         * @var string
         * @uses A ordem tem um pagamento associado creditado, porém, insuficiente.
         */
        const PARTIALLY_PAID = 'partially_paid';

        /**
         * @var string
         * @uses A ordem tem um pagamento associado creditado.
         */
        const PAID = 'paid';

        /**
         * @var string
         * @uses Por alguma razão, a ordem não foi completada.
         */
        const CANCELLED = 'cancelled';

        /**
         * @var string
         * @uses A ordem foi invalidada por vir de um comprador malicioso.
         */
        const INVALID = 'invalid';

        /**
         * @var array
         */
        protected $status = [
            self::CANCELLED,
            self::CONFIRMED,
            self::INVALID,
            self::PAID,
            self::PARTIALLY_PAID,
            self::PAYMENT_IN_PROCESS,
            self::PAYMENT_REQUIRED
        ];
    }