@use(App\Enums\Area)

<dialog x-data='{profile:@json($profile), signoutRoute:@json($signoutRoute)}' class="left" id="profile">
	<header>
		<nav>
			<h6 class="max">Profile</h6>
			<button class="transparent circle large" data-ui="#profile"><i>close</i></button>
		</nav>
	</header>
	<div class="space"></div>
	<div class="dialog-body">
		<div class="section info-head">
			<i class="fill">person_pin</i>
			<p x-text="employeeId"></p>
			<p class="name" x-text="displayName"></p>
			<p class="mail" x-text="mail"></p>
		</div>
		<div class="section info-body">
			<p>
				<span x-text="department"></span>
				<span x-text="title"></span>
			</p>
			<p>
				<span>管理區域</span>
				<span x-show="!roleArea" class="text-danger">未設定</span>
				<div class="user-area">
					<template x-for="area in roleArea">
						<span class="badge" x-text="Area::getLabelByValue(area)"></span>
					</template>
				</div>
			</p>
			<p x-text="company"></p>
		</div>
	</div>
	<a :href="signoutRoute" class="btn-logout button extend circle">
		<i>logout</i>
		<span>登出</span>
	</a>
</dialog>
