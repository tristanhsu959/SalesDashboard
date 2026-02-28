@use(App\Enums\Area)

<dialog x-data='{profile:@json($profile), areaOptions:@json($areaOptions), signoutRoute:@json($signoutRoute)}' class="left small" id="profile">
	<header>
		<nav>
			<h6 class="max">Profile</h6>
			<button class="transparent circle large" data-ui="#profile"><i>close</i></button>
		</nav>
	</header>
	<div class="space"></div>
	<div class="dialog-body">
		<div class="info-head">
			<i class="fill">person_pin</i>
			<p x-text="profile.employeeId"></p>
			<p x-text="profile.displayName" class="name"></p>
			<p x-text="profile.mail"></p>
		</div>
		<div class="info-body">
			<p>
				<span x-text="profile.department"></span>
				<span x-text="profile.title"></span>
			</p>
			<p>
				<span>管理區域</span>
				<span x-show="!profile.roleArea" class="text-danger">未設定</span>
			</p>
			<p x-show="profile.roleArea" class="row wrap auth-area">
				<template x-for="area in profile.roleArea">
					<a class="chip border orange white-text" x-text="areaOptions[area]"></a>
				</template>
			</p>
			<p x-text="profile.company"></p>
		</div>
	</div>
	<a :href="signoutRoute" class="btn-logout button extend circle">
		<i>logout</i>
		<span>登出</span>
	</a>
</dialog>
