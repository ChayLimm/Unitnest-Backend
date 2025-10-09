{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

<x-backpack::menu-item title="Users" icon="la la-question" :link="backpack_url('user')" />
<x-backpack::menu-item title="Buildings" icon="la la-question" :link="backpack_url('building')" />
<x-backpack::menu-item title="Bakong accounts" icon="la la-question" :link="backpack_url('bakong-account')" />
<x-backpack::menu-item title="Settings" icon="la la-question" :link="backpack_url('setting')" />
<x-backpack::menu-item title="Roles" icon="la la-question" :link="backpack_url('role')" />
<x-backpack::menu-item title="Room types" icon="la la-question" :link="backpack_url('room-type')" />
<x-backpack::menu-item title="Rooms" icon="la la-question" :link="backpack_url('room')" />
<x-backpack::menu-item title="Contracts" icon="la la-question" :link="backpack_url('contract')" />
<x-backpack::menu-item title="Services" icon="la la-question" :link="backpack_url('service')" />
<x-backpack::menu-item title="Consumptions" icon="la la-question" :link="backpack_url('consumption')" />
<x-backpack::menu-item title="Transactions" icon="la la-question" :link="backpack_url('transaction')" />
<x-backpack::menu-item title="Payments" icon="la la-question" :link="backpack_url('payment')" />
<x-backpack::menu-item title="Payment items" icon="la la-question" :link="backpack_url('payment-item')" />
<x-backpack::menu-item title="Notifications" icon="la la-question" :link="backpack_url('notification')" />