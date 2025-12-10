@use(App\Enums\Area)

<div class="offcanvas offcanvas-start" tabindex="-1" id="popup-profile" aria-labelledby="popup-profile">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title">Profile</h5>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="section info-head">
			<span class="material-symbols-outlined filled-icon">assignment_ind</span>
			<p>{{ $signinInfo['employeeid'] }}</p>
			<p class="name">{{ $signinInfo['displayname'] }}</p>
			<p class="mail">{{ $signinInfo['mail'] }}</p>
		</div>
		<div class="section info-body">
			<p>
				<span>{{ $signinInfo['department'] }}</span>
				<span>{{ $signinInfo['title'] }}</span>
			</p>
			<p>
				<span>管理區域</span>
				<span>{{-- Area::getLabelByValue($signinInfo['UserAreaId']) --}}</span>
			</p>
			<p>{{ $signinInfo['company'] }}</p>
		</div>
		<a href="{{ route('signout') }}" class="btn btn-signout" type="button">
			<span class="material-symbols-outlined filled-icon">logout</span>
		</a>
	</div>
</div>