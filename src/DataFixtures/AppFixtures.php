<?php

namespace App\DataFixtures;

use App\Entity\CustomerOrder;
use App\Entity\Guitar;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Enum\Availability;
use App\Enum\OrderStatus;
use App\Enum\PaymentMethod;
use App\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fills the database with Shan's Guitar data.
 *
 *     symfony console doctrine:fixtures:load
 *
 * The data comes from the XAMPP export (shansguitar.sql, 9 Nov 2026),
 * converted to the new ERD design:
 *   - the `admins` table is merged into `users` with role = 'admin'
 *   - each order's `items` JSON becomes rows in `order_items`
 *   - password hashes are copied as they are, so everyone keeps their password
 *
 * Fixes made while converting:
 *   - Order #1 pointed to guitar id 1, which doesn't exist. The JSON name
 *     says "Fender Stratocaster II", so it now points to that guitar (SG-0002).
 *   - Empty phone numbers and notes ('') are stored as NULL (= "not given").
 *   - Guitar and admin dates were exported in UTC; they are converted to
 *     Manila time like every other date in the app.
 *
 * Note: order numbers are renumbered 1, 2, 3... because the database
 * gives out new ids. The dates, totals, statuses and items are unchanged.
 */
final class AppFixtures extends Fixture
{
    /** Set to false to load ONLY the real data, without the demo orders. */
    private const LOAD_DEMO_ORDERS = true;

    public function load(ObjectManager $manager): void
    {
        // ------------------------------------------------------------------
        // 1. USERS: the two customers + the admin (merged from `admins`)
        // ------------------------------------------------------------------
        $users = [];
        foreach ($this->userData() as $key => $row) {
            $user = (new User())
                ->setName($row['name'])
                ->setEmail($row['email'])
                ->setPhone($row['phone'])
                ->setPassword($row['password'])
                ->setRole($row['role'])
                ->setCreatedAt($row['created_at']);
            $manager->persist($user);
            $users[$key] = $user;
        }

        // ------------------------------------------------------------------
        // 2. GUITARS, remembered by item code (SG-0002 ...)
        // ------------------------------------------------------------------
        $guitars = [];
        foreach ($this->guitarData() as $row) {
            $guitar = (new Guitar())
                ->setItemCode($row[0])
                ->setName($row[1])
                ->setBrand($row[2])
                ->setCategory($row[3])
                ->setPrice($row[4])
                ->setStock($row[5])
                ->setImage($row[6])
                ->setDescription($row[7])
                ->setIsBestseller($row[8])
                ->setIsNew(false)
                ->setAvailability($row[9])
                ->setCreatedAt($this->utc($row[10]))
                ->setUpdatedAt($this->utc($row[11]));
            $manager->persist($guitar);
            $guitars[$row[0]] = $guitar;
        }

        // ------------------------------------------------------------------
        // 3. ORDERS from the store, oldest first
        // ------------------------------------------------------------------
        foreach ($this->orderData() as $row) {
            $order = $this->makeOrder($users[$row['user']], $guitars, $row);
            if (isset($row['verified_by'])) {
                $order->setPaymentVerifiedBy($users[$row['verified_by']]);
            }
            $manager->persist($order);
        }

        // ------------------------------------------------------------------
        // 4. DEMO ORDERS (made up), dated relative to "now" so the dashboard
        //    always has something for Today's sales, the GCash queue, etc.
        //    They take their units out of stock, like a real checkout does.
        // ------------------------------------------------------------------
        if (self::LOAD_DEMO_ORDERS) {
            foreach ($this->demoOrderData() as $row) {
                $order = $this->makeOrder($users[$row['user']], $guitars, $row);
                if ($order->getStatus() !== OrderStatus::Cancelled) {
                    foreach ($order->getItems() as $item) {
                        $guitar = $item->getGuitar();
                        $guitar->setStock($guitar->getStock() - $item->getQuantity());
                    }
                }
                $manager->persist($order);
            }
        }

        $manager->flush();
    }

    /**
     * Builds one order with its order_items rows.
     *
     * @param array<string, Guitar> $guitars
     */
    private function makeOrder(User $customer, array $guitars, array $row): CustomerOrder
    {
        $order = (new CustomerOrder())
            ->setCustomer($customer)
            ->setFulfillment($row['fulfillment'] ?? 'delivery')
            ->setPaymentMethod($row['method'])
            ->setPaymentRef($row['ref'] ?? null)
            ->setPaymentVerifiedAt(isset($row['verified_at']) ? $this->local($row['verified_at']) : null)
            ->setFullname($row['fullname'])
            ->setPhone($row['phone'])
            ->setAddress($row['address'])
            ->setCity($row['city'])
            ->setNotes($row['notes'])
            ->setStatus($row['status'])
            ->setStatusUpdatedAt(isset($row['status_updated_at']) ? $this->local($row['status_updated_at']) : null)
            ->setCreatedAt($this->local($row['created_at']));

        foreach ($row['items'] as [$itemCode, $quantity, $unitPrice]) {
            $order->addItem(
                (new OrderItem())
                    ->setGuitar($guitars[$itemCode])
                    ->setQuantity($quantity)
                    ->setUnitPrice($unitPrice)
            );
        }

        $order->setTotal($order->calculateTotal());

        return $order;
    }

    /** A date the old database saved in Manila time already (DATETIME columns), or a relative one like "-2 hours". */
    private function local(string $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date);
    }

    /** A date the old database exported in UTC (TIMESTAMP columns) -> Manila time. */
    private function utc(string $date): \DateTimeImmutable
    {
        return (new \DateTimeImmutable($date, new \DateTimeZone('UTC')))
            ->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    }

    private function userData(): array
    {
        return [
            'hochimin' => [
                'name' => 'Hochimin',
                'email' => 'hochimin@gmail.com',
                'phone' => null,
                'password' => '$2y$12$0R73IF2gkg5yeImmo5zMSeLx9oLG134.t22sd/CDDLehEp2qbBMXK',
                'role' => UserRole::User,
                'created_at' => $this->local('2026-09-12 09:56:25'),
            ],
            'johncruz1' => [
                'name' => 'johncruz1',
                'email' => 'johncruz1@gmail.com',
                'phone' => '0968756482',
                'password' => '$2y$12$8WfJEoYphzai9ViqinB4NOqz81ns/lSW1/FpclVHXg2xiKAWiiNpO',
                'role' => UserRole::User,
                'created_at' => $this->local('2026-09-14 16:17:02'),
            ],
            // From the old `admins` table. Log in with the same password as before.
            'shan' => [
                'name' => 'Shan',
                'email' => 'admin@shansguitar.com',
                'phone' => null,
                'password' => '$2y$12$Pi0VVrSIpXLE2QscECJml.DfBda/dwsJ2Umd4x.EHonvpvJaEFNJa',
                'role' => UserRole::Admin,
                'created_at' => $this->utc('2026-09-12 06:39:09'),
            ],
        ];
    }

    /** [item code, name, brand, category, price, stock, image, description, bestseller, availability, created (UTC), updated (UTC)] */
    private function guitarData(): array
    {
        return [
            ['SG-0002', 'Fender Stratocaster II', 'Fender', 'Electric', '35500.00', 10, 'images/products/Fender_Strat_2_CoralRed.webp', 'Alder body.', false, Availability::InStore, '2026-09-13 18:10:49', '2026-09-16 11:12:22'],
            ['SG-0003', 'Yamaha FG800', 'Yamaha', 'Acoustic', '15500.00', 14, 'images/products/Yamaha_FG800.jpg', 'Solid spruce top dreadnought with a big, warm voice. The best first serious acoustic you can buy.', true, Availability::InStore, '2026-09-13 18:10:49', '2026-09-16 08:02:15'],
            ['SG-0004', 'Fender Stratocaster White', 'Fender', 'Electric', '45000.00', 6, 'images/products/Fender-player-ii-stratocaster-wh.jpg', 'Olympic White finish with a rosewood fingerboard — bright, articulate and endlessly versatile.', true, Availability::InStore, '2026-09-13 18:10:49', '2026-09-16 08:02:15'],
            ['SG-0005', 'PRS Custom 24 10-Top', 'PRS', 'Electric', '96500.00', 4, 'images/products/prs_custom_24_10_top.jpg', 'Flame maple 10-Top, 85/15 pickups and a 5-way blade — modern precision with vintage soul.', true, Availability::InStore, '2026-09-13 18:10:49', '2026-09-16 08:02:15'],
            ['SG-0006', 'Gibson Les Paul Standard', 'Gibson', 'Electric', '105500.00', 5, 'images/products/2019-Gibson-LP-Std-a-1.webp', 'Mahogany body, carved maple cap and Burstbucker humbuckers. Thick, singing sustain.', true, Availability::InStore, '2026-09-13 18:10:49', '2026-09-16 12:49:22'],
            ['SG-0007', 'Epiphone SG Standard', 'Epiphone', 'Electric', '25000.00', 7, 'images/products/epiphone_sg_standard.jpg', 'Lightweight double-cutaway with fierce mid-range bite — a rock machine at a fair price.', true, Availability::InStore, '2026-09-13 18:10:49', '2026-09-16 08:02:15'],
            ['SG-0008', 'Martin D-28', 'Martin', 'Acoustic', '178000.00', 1, 'images/products/Martin_D28.jpg', 'The benchmark dreadnought since 1931. East Indian rosewood back and sides, Sitka spruce top.', true, Availability::InStore, '2026-09-13 18:10:49', '2026-09-16 13:06:33'],
            ['SG-0009', 'Taylor GS Mini Mahogany', 'Taylor', 'Acoustic', '39500.00', 9, 'images/products/Taylor_GS_Mini_Mahogany_Acoustic.webp', 'Compact scaled-down Grand Symphony with surprising volume. Travel-ready with a gig bag.', true, Availability::InStore, '2026-09-13 18:10:49', '2026-09-16 08:02:15'],
            ['SG-0010', 'Fender Player Jazz Bass', 'Fender', 'Bass', '48000.00', 6, 'images/products/Fender_Player_Jazz_Bazz.jpg', 'Two Player Series single-coils and a slim neck — the go-to bass for every genre.', true, Availability::InStore, '2026-09-13 18:10:49', '2026-09-16 08:02:15'],
            ['SG-0011', 'Yamaha TRBX304', 'Yamaha', 'Bass', '22500.00', 9, 'uploads/guitars/gtr_8a9db39b7340.jpg', 'Mahogany body, active 2-band EQ and a 5-way Performance EQ switch.', false, Availability::InStore, '2026-09-13 18:10:49', '2026-09-16 08:02:15'],
            ['SG-0012', 'Cordoba C5 Classical', 'Cordoba', 'Classical', '18500.00', 4, 'images/products/cordoba_c5_classical.jpg', 'Solid cedar top nylon-string with a wide, comfortable neck for fingerstyle players.', false, Availability::Online, '2026-09-13 18:10:49', '2026-09-16 19:15:54'],
            ['SG-0013', 'Gretsch G2622 Streamliner', 'Gretsch', 'Electric', '42500.00', 4, 'images/products/gretsch_g2622_streamliner.webp', 'Centre-block semi-hollow with Broad’Tron pickups — jangle, twang and feedback control.', false, Availability::InStore, '2026-09-13 18:10:49', '2026-09-16 08:02:15'],
            ['SG-0014', 'Ernie Ball Slinky (3-Pack)', 'Ernie Ball', 'Accessory', '1450.00', 25, 'images/products/ernie_ball_slinky_electric_9_42_3pack.jpg', 'Hybrid Slinky 9-46 nickel wound strings. Three sets, the industry standard.', true, Availability::InStore, '2026-09-13 18:10:49', '2026-09-16 08:02:15'],
            ['SG-0015', 'Fender Deluxe Gig Bag', 'Fender', 'Accessory', '3500.00', 15, 'images/products/Fender_Deluxe_Gig_Bag.jpg', '25 mm padding, dual shoulder straps and an accessory pocket. Fits most electrics.', false, Availability::PreOrder, '2026-09-13 18:10:49', '2026-09-16 09:09:28'],
            ['SG-0016', 'Yamaha Pacifica Standard Plus', 'Yamaha', 'Electric', '150000.00', 10, 'uploads/guitars/gtr_596415a93fb6.jpg', 'Alder body, slim C-shape maple neck, and Reflectone pickups co-developed with Rupert Neve Designs. Clear, highly articulate tones with versatile HSS switching.', false, Availability::ComingSoon, '2026-09-14 08:38:46', '2026-09-16 09:09:05'],
        ];
    }

    /** The 8 real orders. items = [item code, quantity, unit price]. Old order number in the comment. */
    private function orderData(): array
    {
        return [
            // old #1 (JSON said guitar id 1 — fixed to SG-0002, see top of file)
            ['user' => 'hochimin', 'items' => [['SG-0002', 1, '35500.00']], 'method' => PaymentMethod::Cod,
             'fullname' => 'Hochimin', 'phone' => '09877765341', 'address' => '76barangay6 34', 'city' => 'cebu city negros oriental',
             'notes' => 'send me an electronic receipt', 'status' => OrderStatus::Shipped,
             'status_updated_at' => '2026-09-12 10:04:04', 'created_at' => '2026-09-12 10:04:04'],
            // old #2
            ['user' => 'johncruz1', 'items' => [['SG-0003', 2, '15500.00'], ['SG-0004', 1, '45000.00'], ['SG-0005', 1, '96500.00']], 'method' => PaymentMethod::Cod,
             'fullname' => 'johncruz', 'phone' => '09877765341', 'address' => 'norsu dumaguete', 'city' => 'bacoong city',
             'notes' => 'put lots of bubble wrappings', 'status' => OrderStatus::Cancelled,
             'status_updated_at' => '2026-09-16 03:06:15', 'created_at' => '2026-09-14 16:21:54'],
            // old #3
            ['user' => 'johncruz1', 'items' => [['SG-0006', 1, '105500.00']], 'method' => PaymentMethod::Cod,
             'fullname' => 'johncruz1', 'phone' => '0934567891', 'address' => '86 Dauin Boloc-boloc street.', 'city' => 'bacoong city',
             'notes' => '', 'status' => OrderStatus::Pending,
             'status_updated_at' => '2026-09-14 21:11:43', 'created_at' => '2026-09-14 21:11:43'],
            // old #5
            ['user' => 'johncruz1', 'items' => [['SG-0011', 1, '22500.00']], 'method' => PaymentMethod::Cod,
             'fullname' => 'johncruz1', 'phone' => '05876352454', 'address' => '86 Dauin Boloc-boloc street.', 'city' => 'dauin univercity',
             'notes' => '', 'status' => OrderStatus::Pending,
             'status_updated_at' => '2026-09-15 00:41:29', 'created_at' => '2026-09-15 00:41:29'],
            // old #6
            ['user' => 'johncruz1', 'items' => [['SG-0005', 1, '96500.00']], 'method' => PaymentMethod::Cod,
             'fullname' => 'johncruz1', 'phone' => '09877765341', 'address' => '86 Dauin Boloc-boloc street.', 'city' => 'dauin univercity',
             'notes' => '', 'status' => OrderStatus::Shipped,
             'status_updated_at' => '2026-09-17 03:18:14', 'created_at' => '2026-09-15 01:16:03'],
            // old #15
            ['user' => 'hochimin', 'items' => [['SG-0006', 1, '105500.00']], 'method' => PaymentMethod::PickupPay,
             'fullname' => 'Hochimin', 'phone' => '0984385673', 'address' => '70 sibuland street poblacion 17', 'city' => 'Dumaguete CIty, Negros Oriental',
             'notes' => 'please add more proper wrapping', 'status' => OrderStatus::Pending,
             'created_at' => '2026-09-16 20:49:22'],
            // old #16
            ['user' => 'hochimin', 'items' => [['SG-0008', 1, '178000.00']], 'method' => PaymentMethod::PickupPay,
             'fullname' => 'Hochimin', 'phone' => '0968756482', 'address' => '70 sibuland street poblacion 17', 'city' => 'Dumaguete CIty, Negros Oriental',
             'notes' => 'none', 'status' => OrderStatus::Cancelled,
             'status_updated_at' => '2026-09-16 21:06:33', 'created_at' => '2026-09-16 21:00:53'],
            // old #17 — GCash, verified by Shan
            ['user' => 'hochimin', 'items' => [['SG-0012', 1, '18500.00']], 'method' => PaymentMethod::Gcash, 'ref' => '1234567890123',
             'verified_at' => '2026-09-17 03:16:24', 'verified_by' => 'shan',
             'fullname' => 'Hochimin', 'phone' => '0968756482', 'address' => '86 Dauin Boloc-boloc street.', 'city' => 'Dumaguete CIty, Negros Oriental',
             'notes' => 'none', 'status' => OrderStatus::Confirmed,
             'status_updated_at' => '2026-09-17 03:16:24', 'created_at' => '2026-09-17 03:15:54'],
        ];
    }

    /** Made-up orders for the demo, dated relative to the moment you load the fixtures. */
    private function demoOrderData(): array
    {
        return [
            // Yesterday, delivered: gives "vs yesterday" something to compare with
            ['user' => 'johncruz1', 'items' => [['SG-0003', 1, '15500.00']], 'method' => PaymentMethod::PickupPay, 'fulfillment' => 'pickup',
             'fullname' => 'johncruz1', 'phone' => '0968756482', 'address' => '86 Dauin Boloc-boloc street.', 'city' => 'Dumaguete City',
             'notes' => null, 'status' => OrderStatus::Delivered,
             'status_updated_at' => '-20 hours', 'created_at' => '-1 day'],
            // Today, COD, confirmed: ready for "Mark shipped"
            ['user' => 'hochimin', 'items' => [['SG-0014', 2, '1450.00'], ['SG-0015', 1, '3500.00']], 'method' => PaymentMethod::Cod,
             'fullname' => 'Hochimin', 'phone' => '0968756482', 'address' => '70 sibuland street poblacion 17', 'city' => 'Dumaguete City, Negros Oriental',
             'notes' => null, 'status' => OrderStatus::Confirmed,
             'status_updated_at' => '-3 hours', 'created_at' => '-4 hours'],
            // Today, GCash, NOT verified yet: shows up in "GCash to verify"
            ['user' => 'johncruz1', 'items' => [['SG-0009', 1, '39500.00']], 'method' => PaymentMethod::Gcash, 'ref' => '4829106733451',
             'fullname' => 'johncruz1', 'phone' => '0968756482', 'address' => '86 Dauin Boloc-boloc street.', 'city' => 'Dumaguete City',
             'notes' => 'Please call before delivery', 'status' => OrderStatus::Pending,
             'created_at' => '-50 minutes'],
        ];
    }
}
