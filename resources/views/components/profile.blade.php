
<dialog x-data='userProfile(@json($currentUser["profile"]), @json($currentUser["options"]))' class="left small" id="profile">
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
			<button data-ui="#profileEdit" class="transparent circle orange white-text profile-edit">
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
				<template x-for="areaId in profile.roleArea" :key="areaId">
					<button class="chip round medium small-elevate secondary white-text">
						<span x-text="options.area[areaId]">Input</span>
					</button >
				</template>
			</p>
			<!--p x-text="profile.company"></p-->
		</div>
	</div>
	<a :href="options.signoutRoute" class="btn-logout button extend circle">
		<i>logout</i>
		<span>登出</span>
	</a>
</dialog>

<dialog x-data='userProfileEdit(@json($currentUser["profile"]), @json($currentUser["options"]))' class="left small active" id="profileEdit">
	<header>
		<nav>
			<h6 class="max">編輯 Profile</h6>
			<button class="transparent circle large" data-ui="#profile"><i>close</i></button>
		</nav>
	</header>
	<div class="space"></div>
	<div class="dialog-body">
		<div class="info-body">
		<form :action="options.updateRoute" method="post" novalidate @submit.prevent="validate()">
			<input type="hidden" name="id" :value="formData.userId" x-model="formData.userId">
			@csrf
			<p x-text="formData.userAccount"></p>
			<div class="field label border field-purple prefix">
				<i>
					<label class="checkbox">
						<input type="checkbox" value="1" x-model="fieldEnabled.displayName" :check="fieldEnabled.displayName == 1">
						<span></span>
					</label>
				</i>
				<input type="text" name="displayName" maxlength="15" x-model="formData.userDisplayName" :disabled="fieldEnabled.displayName == 0">
				<label>顯示名稱</label>
			</div>
		
			<div class="field label border field-purple prefix">
				<i>
					<label class="checkbox">
						<input type="checkbox" value="1" x-model="fieldEnabled.department" :check="fieldEnabled.department == 1">
						<span></span>
					</label>
				</i>
				<input type="text" name="department" maxlength="15" required x-model="formData.department" :disabled="fieldEnabled.department == 0">
				<label>部門</label>
			</div>
			
			<div class="field label border field-purple prefix">
				<i>
					<label class="checkbox">
						<input type="checkbox" value="1" x-model="fieldEnabled.email" :check="fieldEnabled.email == 1">
						<span></span>
					</label>
				</i>
				<input type="text" name="email" maxlength="50" required x-model="formData.email" :disabled="fieldEnabled.email == 0">
				<label>EMail</label>
			</div>
			<div class="field label border field-purple prefix" :class="Helper.hasError(errors, 'password')">
				<i>
					<label class="checkbox">
						<input type="checkbox" value="1" x-model="fieldEnabled.password" :check="fieldEnabled.password == 1">
						<span></span>
					</label>
				</i>
				<input type="password" name="password" maxlength="15" required x-model="formData.userPassword" @input="errors.delete('password')" :disabled="fieldEnabled.password == 0">
				<label>密碼</label>
				<output class="red-text">英文+數字六個字元以上</output>
			</div>
			
			<p class="red-text"><i class="small">info</i><span>請勾選要編輯的欄位</span><p/>
			<nav class="toolbar">
				<button type="submit" class="button btn-save btn-primary slow-ripple">儲存</button>
				<button @click="reset() "type="button" class="button btn-cancel border slow-ripple">重置</button>
			
			</nav>
		</form>
		</div>
	</div>
</dialog>