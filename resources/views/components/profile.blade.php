
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
			<!--p x-text="profile.employeeId"></p-->
			<p x-text="profile.userDisplayName" class="name"></p>
			<p x-text="profile.email"></p>
			<button class="transparent circle orange white-text profile-edit">
				<i>person_edit</i>
			</button >
		</div>
		<div class="info-body">
			<p>
				<span x-text="profile.department"></span>
				<!--span x-text="profile.title"></span-->
			</p>
			<p>
				<span>管理區域</span>
				<span x-show="profile.roleArea.length <= 0 " class="text-danger">未設定</span>
			</p>
			<p x-show="profile.roleArea.length > 0" class="row wrap auth-area">
				<template x-for="area in profile.roleArea">
					<button class="chip round medium small-elevate secondary white-text">
						<span x-text="areaOptions[area]">Input</span>
					</button >
				</template>
			</p>
			<!--p x-text="profile.company"></p-->
		</div>
	</div>
	<a :href="signoutRoute" class="btn-logout button extend circle">
		<i>logout</i>
		<span>登出</span>
	</a>
</dialog>
