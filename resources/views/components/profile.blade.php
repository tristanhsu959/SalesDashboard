
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

<dialog x-data='userProfileEdit(@json($currentUser["profile"]), @json($currentUser["options"]))' class="left small" id="profileEdit">
	<header>
		<nav>
			<h6 class="max">編輯 Profile</h6>
			<button class="transparent circle large" data-ui="#profileEdit"><i>close</i></button>
		</nav>
	</header>
	<div class="space"></div>
	<div class="dialog-body">
		<div class="info-body">
		<form :action="options.updateRoute" method="post" novalidate @submit.prevent="validate()">
			<input type="hidden" name="id" :value="formData.userId" x-model="formData.userId">
			@csrf
			<h6 x-text="formData.userAccount"></h6>
			<div class="field label border field-purple prefix">
				<i>
					<label class="checkbox">
						<input type="checkbox" value="displayName" x-model="fieldEnabled">
						<span></span>
					</label>
				</i>
				<input type="text" name="displayName" maxlength="15" x-model="formData.userDisplayName" :disabled="!fieldEnabled.includes('displayName')">
				<label>顯示名稱</label>
			</div>
		
			<div class="field label border field-purple prefix">
				<i>
					<label class="checkbox">
						<input type="checkbox" value="department" x-model="fieldEnabled">
						<span></span>
					</label>
				</i>
				<input type="text" name="department" maxlength="15" required x-model="formData.department" :disabled="!fieldEnabled.includes('department')">
				<label>部門</label>
			</div>
			
			<div class="field label border field-purple prefix">
				<i>
					<label class="checkbox">
						<input type="checkbox" value="email" x-model="fieldEnabled">
						<span></span>
					</label>
				</i>
				<input type="text" name="email" maxlength="50" required x-model="formData.email" :disabled="!fieldEnabled.includes('email')">
				<label>EMail</label>
			</div>
			<nav class="no-space">
				<div class="field label border field-purple prefix max" :class="Helper.hasError(errors, 'password')">
					<i>
						<label class="checkbox">
							<input type="checkbox" value="password" x-model="fieldEnabled">
							<span></span>
						</label>
					</i>
					<input :type="showPassword ? 'text':'password'" name="password" maxlength="15" required x-model="formData.userPassword" @input="errors.delete('password')" :disabled="!fieldEnabled.includes('password')">
					<label>密碼</label>
				</div>
				<button type="button" class="large square" @click="showPassword = !showPassword" :disabled="!fieldEnabled.includes('password')">
					<i x-show="!showPassword">visibility</i>
					<i x-show="showPassword">visibility_off</i>
				</button>
			</nav>
			
			<output class="red-text medium-text">英文+數字六個字元以上</output>
			<p class="red-text medium-text"><i class="small">info</i><span>請勾選要編輯的欄位</span></p>
			
			<nav class="toolbar">
				<button type="submit" class="button btn-save btn-primary slow-ripple" :disabled="fieldEnabled.length <= 0">儲存</button>
				<button @click="reset() "type="button" class="button btn-cancel border slow-ripple">重置</button>
			
			</nav>
		</form>
		</div>
	</div>
</dialog>